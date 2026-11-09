# Curso de Teologia IBADEG

O curso fica em **Ensino → Estudos → Curso de Teologia — IBADEG**. Cada uma das 21 disciplinas tem apostila PDF, prova original PDF e formulário interativo. O PDF consolidado das provas fica no anexo do curso.

Para importar em outro ambiente, disponibilize a pasta original com `LIVROS`, `PROVAS` e `SITE PROVAS/index.html`, execute as migrações e rode:

```powershell
php artisan ensino:import-ibadeg 'D:\IBADEG'
```

Os 43 PDFs são copiados por stream para o disco público, em `studies/ibadeg`. O servidor precisa servir `public/storage` (configuração padrão: `php artisan storage:link`). Esses arquivos devem acompanhar o backup/deploy do storage; não são versionados no Git.

A importação lê o JSON `EXAMS` da página fornecida sem executar JavaScript. Preserva os 235 enunciados em 761 itens: 699 objetivos e 62 abertos. Associa os PDFs pelo número da disciplina. A repetição do comando mantém os formulários, as questões e as tentativas já registradas; não sobrescreve edições dos professores.

A nota automática é `10 × acertos / itens objetivos com gabarito`. Cada item C/E e cada associação vale um ponto. As questões abertas ficam fora dessa nota, seguindo o HTML original, e mostram a resposta de referência quando disponível. Uma questão aberta do original está sem referência. Os avisos de gabarito incerto do original são preservados; precisam de revisão pedagógica.

Depois do envio, o aluno recebe sua nota e a revisão das respostas. A página de resultado usa a sessão de quem respondeu, não aceita IDs de tentativas na URL e continua disponível ao atualizar a página. O histórico permanece nos formulários do curso.

As provas IBADEG usam a apresentação compacta do HTML original: um enunciado por questão, itens C/E na mesma linha e todas as questões em uma página, com **Corrigir Prova** ao final. Depois da correção, o botão **Verificar** abre somente o trecho correspondente do livro, com título e página do PDF. O agrupamento é apenas visual e preserva os IDs e as tentativas existentes.

Os trechos estão em `database/data/ibadeg-book-excerpts.json`, com o hash SHA-256 do PDF de origem. A associação usa uma assinatura do enunciado e do gabarito; editar qualquer um deles invalida a associação antiga. O índice inicial tem 609 associações nos 761 itens. Nos 152 sem correspondência segura, a tela informa a ausência do trecho, sem substituir a citação por uma resposta inventada. Os enunciados não expõem as citações antes do envio.

`scripts/build_ibadeg_excerpts.py` extrai candidatos dos PDFs usando pypdf e busca textual local. `database/data/ibadeg-excerpt-overrides.json` contém seleções por página e âncoras para tópicos ambíguos. Homilética requer OCR porque o PDF contém imagens; `scripts/ocr_ibadeg_pages.ps1` usa o reconhecedor local do Windows nas páginas renderizadas com pdftoppm. O índice distribuído não depende de Python nem OCR no servidor. O cache de páginas e os candidatos ficam apenas em `storage/app/ibadeg`, fora do disco público.

Para regenerar candidatos e publicar associações a partir das apostilas e do cache OCR:

```powershell
python scripts/build_ibadeg_excerpts.py 'D:\IBADEG' --publish
```

Os candidatos textuais precisam de conferência pedagógica, principalmente quando o próprio gabarito original estiver marcado para revisão. As reticências indicam omissões; a paginação identifica a página do arquivo PDF.

Ao editar alternativas importadas, mantenha o formato `chave::texto` para preservar chaves como C/E e números. O professor pode desmarcar a indicação de revisão depois de conferir o gabarito.

Validação automatizada:

```powershell
php vendor/bin/phpunit tests/Feature/IbadegCourseTest.php
```

A cópia JSON em `tests/Fixtures/ibadeg-exams.json` serve para testar o conjunto completo de questões. O teste cria PDFs temporários e usa SQLite em memória, sem alterar o banco da aplicação.
