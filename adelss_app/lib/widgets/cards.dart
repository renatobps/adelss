import 'package:flutter/material.dart';

import '../api/models.dart';
import '../theme.dart';
import '../utils/format.dart';
import 'ui.dart';

class ScheduleCard extends StatelessWidget {
  const ScheduleCard({
    super.key,
    required this.item,
    this.onConfirm,
    this.onReject,
    this.busy = false,
  });

  final ScheduleItem item;
  final VoidCallback? onConfirm;
  final VoidCallback? onReject;
  final bool busy;

  StatusTone get _tone {
    switch (item.status) {
      case 'confirmado':
        return StatusTone.success;
      case 'recusado':
        return StatusTone.danger;
      default:
        return StatusTone.pending;
    }
  }

  @override
  Widget build(BuildContext context) {
    return SurfaceCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              DateStamp(date: item.startsAt),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Text(
                          item.typeLabel.toUpperCase(),
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            letterSpacing: 0.8,
                            color: AdelssColors.gold,
                          ),
                        ),
                        const Spacer(),
                        StatusPill(label: item.statusLabel, tone: _tone),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(item.title, style: Theme.of(context).textTheme.titleMedium),
                    const SizedBox(height: 4),
                    Text(
                      [
                        if (item.area != null && item.area!.isNotEmpty) item.area,
                        formatTime(item.startsAt),
                        if (item.location != null && item.location!.isNotEmpty) item.location,
                      ].whereType<String>().join(' · '),
                      style: Theme.of(context).textTheme.bodyMedium,
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (item.canConfirm || item.canReject) ...[
            const SizedBox(height: 14),
            Row(
              children: [
                if (item.canConfirm)
                  Expanded(
                    child: FilledButton(
                      onPressed: busy ? null : onConfirm,
                      child: const Text('Confirmar'),
                    ),
                  ),
                if (item.canConfirm && item.canReject) const SizedBox(width: 8),
                if (item.canReject)
                  Expanded(
                    child: OutlinedButton(
                      onPressed: busy ? null : onReject,
                      child: const Text('Recusar'),
                    ),
                  ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}

class EventCard extends StatelessWidget {
  const EventCard({super.key, required this.event, this.onTap});

  final EventItem event;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return SurfaceCard(
      onTap: onTap,
      child: Row(
        children: [
          DateStamp(date: event.startsAt),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (event.categoryName != null)
                  Text(
                    event.categoryName!.toUpperCase(),
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 0.7,
                      color: AdelssColors.gold,
                    ),
                  ),
                Text(event.title, style: Theme.of(context).textTheme.titleMedium),
                const SizedBox(height: 4),
                Text(
                  [
                    formatTime(event.startsAt, allDay: event.allDay),
                    if (event.location != null && event.location!.isNotEmpty) event.location,
                  ].join(' · '),
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              ],
            ),
          ),
          const Icon(Icons.chevron_right, color: AdelssColors.muted),
        ],
      ),
    );
  }
}
