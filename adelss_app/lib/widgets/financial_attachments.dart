import 'package:file_picker/file_picker.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api/api_client.dart';
import '../api/api_exception.dart';
import '../api/models.dart';
import '../api/repository.dart';
import '../config.dart';
import '../theme.dart';

class PendingUpload {
  const PendingUpload({required this.filename, required this.bytes, this.mime});

  final String filename;
  final Uint8List bytes;
  final String? mime;

  MultipartUpload toMultipart() => MultipartUpload(
        filename: filename,
        bytes: bytes,
        mime: mime,
      );
}

Future<List<PendingUpload>> pickReceiptFiles({required bool camera}) async {
  if (camera && !kIsWeb) {
    final shot = await ImagePicker().pickImage(
      source: ImageSource.camera,
      imageQuality: 85,
      maxWidth: 2000,
    );
    if (shot == null) return const [];
    return [
      PendingUpload(
        filename: shot.name.isNotEmpty ? shot.name : 'recibo.jpg',
        bytes: await shot.readAsBytes(),
        mime: 'image/jpeg',
      ),
    ];
  }

  final files = await FilePicker.pickFiles(
    type: FileType.custom,
    allowedExtensions: const ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'heic', 'heif'],
  );
  if (files.isEmpty) return const [];

  final uploads = <PendingUpload>[];
  for (final file in files) {
    final bytes = await file.readAsBytes();
    if (bytes.isEmpty) continue;
    uploads.add(
      PendingUpload(
        filename: file.name,
        bytes: bytes,
        mime: file.extension == 'pdf' ? 'application/pdf' : null,
      ),
    );
  }
  return uploads;
}

Future<void> showReceiptSourceSheet({
  required BuildContext context,
  required Future<void> Function(List<PendingUpload> files) onPicked,
}) async {
  final source = await showModalBottomSheet<String>(
    context: context,
    showDragHandle: true,
    builder: (context) => SafeArea(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (!kIsWeb)
            ListTile(
              leading: const Icon(Icons.photo_camera_outlined),
              title: const Text('Tirar foto'),
              onTap: () => Navigator.pop(context, 'camera'),
            ),
          ListTile(
            leading: const Icon(Icons.attach_file),
            title: const Text('Escolher arquivo'),
            subtitle: const Text('Imagem ou PDF, até 10 MB'),
            onTap: () => Navigator.pop(context, 'file'),
          ),
        ],
      ),
    ),
  );
  if (source == null) return;
  final files = await pickReceiptFiles(camera: source == 'camera');
  if (files.isEmpty) return;
  await onPicked(files);
}

class AttachmentSection extends StatelessWidget {
  const AttachmentSection({
    super.key,
    required this.api,
    required this.attachments,
    required this.onAdd,
    this.onDelete,
    this.busy = false,
  });

  final AdelssRepository api;
  final List<FinancialAttachment> attachments;
  final VoidCallback onAdd;
  final Future<void> Function(FinancialAttachment file)? onDelete;
  final bool busy;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text('Comprovantes', style: Theme.of(context).textTheme.titleMedium),
            const Spacer(),
            TextButton.icon(
              onPressed: busy ? null : onAdd,
              icon: busy
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.upload_file_outlined),
              label: Text(busy ? 'Enviando...' : 'Enviar arquivo'),
            ),
          ],
        ),
        if (attachments.isEmpty)
          const Padding(
            padding: EdgeInsets.only(bottom: 8),
            child: Text('Nenhum arquivo anexado.'),
          )
        else
          ...attachments.map(
            (file) => Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Material(
                color: AdelssColors.paper,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                  side: const BorderSide(color: AdelssColors.line),
                ),
                child: ListTile(
                  leading: _AttachmentThumb(api: api, file: file),
                  title: Text(file.fileName, maxLines: 1, overflow: TextOverflow.ellipsis),
                  subtitle: Text(
                    file.generated
                        ? 'Emitido pelo sistema'
                        : file.available
                            ? 'Enviado por upload'
                            : 'Arquivo ausente no servidor',
                  ),
                  onTap: () => openFinancialApiFile(
                    context: context,
                    api: api,
                    path: file.apiPath ??
                        '/financial/transactions/${file.transactionId}/attachments/${file.id}',
                    filename: file.fileName,
                    isImage: file.isImage,
                  ),
                  trailing: onDelete == null
                      ? null
                      : IconButton(
                          onPressed: () => onDelete!(file),
                          icon: const Icon(Icons.delete_outline),
                        ),
                ),
              ),
            ),
          ),
      ],
    );
  }
}

class _AttachmentThumb extends StatelessWidget {
  const _AttachmentThumb({required this.api, required this.file});

  final AdelssRepository api;
  final FinancialAttachment file;

  @override
  Widget build(BuildContext context) {
    if (!file.isImage || file.apiPath == null) {
      return Icon(file.isImage ? Icons.image_outlined : Icons.picture_as_pdf_outlined);
    }
    return ClipRRect(
      borderRadius: BorderRadius.circular(8),
      child: Image.network(
        '${AppConfig.apiBaseUrl}${AppConfig.apiPrefix}${file.apiPath}',
        width: 44,
        height: 44,
        fit: BoxFit.cover,
        headers: api.authHeaders(),
        errorBuilder: (context, error, stackTrace) => const Icon(Icons.image_outlined),
      ),
    );
  }
}

Future<void> openFinancialApiFile({
  required BuildContext context,
  required AdelssRepository api,
  required String path,
  required String filename,
  required bool isImage,
}) async {
  try {
    final bytes = await api.financialFileBytes(path);
    if (!context.mounted) return;
    await showFinancialFile(
      context,
      bytes: bytes,
      filename: filename,
      isImage: isImage,
    );
  } catch (error) {
    if (context.mounted) showUploadError(context, error);
  }
}

Future<void> showFinancialFile(
  BuildContext context, {
  required Uint8List bytes,
  required String filename,
  required bool isImage,
}) async {
  if (isImage) {
    await showDialog<void>(
      context: context,
      builder: (context) => Dialog(
        child: InteractiveViewer(
          child: Image.memory(bytes),
        ),
      ),
    );
    return;
  }

  final opened = await launchUrl(
    Uri.dataFromBytes(bytes, mimeType: 'application/pdf'),
    mode: LaunchMode.externalApplication,
  );
  if (!opened && context.mounted) {
    showUploadError(context, 'Não foi possível abrir $filename.');
  }
}

Future<void> uploadPickedReceipts({
  required AdelssRepository api,
  required int transactionId,
  required List<PendingUpload> files,
}) {
  return api.uploadFinancialAttachments(
    transactionId: transactionId,
    files: files.map((file) => file.toMultipart()).toList(),
  );
}

void showUploadError(BuildContext context, Object error) {
  final message = error is ApiException ? error.message : '$error';
  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
}
