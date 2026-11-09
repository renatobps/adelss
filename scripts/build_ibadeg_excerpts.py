"""Extrai candidatos de trechos das apostilas, sem gerar ou reescrever respostas.

Use o Python com pypdf disponível. O cache contém apenas a extração dos PDFs.
As associações selecionadas são gravadas em database/data/ibadeg-book-excerpts.json.
"""

import argparse
import collections
import difflib
import hashlib
import json
import math
import re
import unicodedata
from pathlib import Path


STOP = set("a o as os um uma uns umas de da do das dos e em no na nos nas para por com ao aos que se seu sua seus suas ou como qual quais quem quando onde porque pois pelo pela pelos pelas mais muito entre sobre ate sao ser era eram foi foram tem ter esta este isto essa esse estas estes isso aquelas aqueles toda todo todas todos marque questoes questao correta correto corretas corretos errada errado erradas errados alternativa alternativas resposta respostas verdadeiro falso acordo relacione coluna complete frases abaixo podemos afirmar pode afirmar estudado assinale biblia biblica biblico biblicas biblicos deus livro livros todas nenhuma ambas algumas algum letra item indique significa significado nao sim segundo estudo estudos refere referentes referente numero relacao".split())


def normalized(value):
    return "".join(c for c in unicodedata.normalize("NFKD", value.lower()) if not unicodedata.combining(c))


def tokens(value):
    return {word[:7] for word in re.findall(r"[a-z0-9]+", normalized(value)) if len(word) > 2 and word not in STOP}


def clean_page(value):
    lines = [line.strip() for line in value.splitlines()]
    lines = [line for line in lines if 'instituto educacional' not in normalized(line)
             and normalized(line) not in {"ibadeg", "teologico do gama", "homiletic a"}
             and not re.fullmatch(r"\d{1,3}", line)]
    value = "\n".join(lines)
    value = re.sub(r"(\w)-\s*\n\s*(\w)", r"\1\2", value)
    return re.sub(r"[ \t]+", " ", value).strip()


def passages(pages):
    results = []
    for number, raw in enumerate(pages, 1):
        if number <= 5:
            continue
        page = clean_page(raw)
        top = normalized(page[:600])
        if any(marker in top for marker in ["atividade de apoio", "atividades de apoio", "avaliacao final", "referencias bibliograficas", "indice", "sumario", "exercicios"]):
            continue
        if page.count("....") > 3 or page.count("___") > 3 or len(re.findall(r'\(\s*\)', page)) > 2:
            continue
        # Apenas janelas contíguas do PDF, com limites em linhas reais.
        lines = [line for line in page.splitlines() if line]
        for start in range(len(lines)):
            chunk = []
            for line in lines[start:]:
                if len(" ".join(chunk + [line])) > 850:
                    break
                chunk.append(line)
                if len(" ".join(chunk)) >= 430:
                    text = " ".join(chunk)
                    results.append({"page": number, "text": text, "terms": tokens(text), "words": normalized(text).split()})
                    break
            else:
                if len(" ".join(chunk)) >= 100:
                    text = " ".join(chunk)
                    results.append({"page": number, "text": text, "terms": tokens(text), "words": normalized(text).split()})
    return results


def rows(exam):
    for q in exam["questions"]:
        instruction = f'{q["n"]}) {q["instruction"]}'
        kind = q["type"]
        if kind == "ce":
            for part in q["parts"]:
                yield q, f'{instruction} — {part["letter"]}) {part["text"]}', part["correct"], part["text"]
        elif kind == "single":
            correct = q["correctLetter"]
            yield q, instruction, correct, next(p["text"] for p in q["parts"] if p["letter"] == correct)
        elif kind == "match":
            for i, part in enumerate(q["right"]):
                answer = next(p["text"] for p in q["left"] if str(p["key"]) == str(part["answerKey"]))
                yield q, f'{instruction} — Item {i+1}: {part["text"]}', str(part["answerKey"]), part["text"] + " " + answer
        elif kind == "fill":
            yield q, instruction, q["answer"], q["answer"]
        elif kind == "fill_multi":
            for part in q["parts"]:
                yield q, f'{instruction} — {part["letter"]}) {part["prompt"]}', part["answer"], part["prompt"] + " " + part["answer"]


def candidates(exam, book):
    chunks = passages(book["pages"])
    frequencies = collections.Counter(term for chunk in chunks for term in chunk["terms"])
    idf = {term: math.log(1 + len(chunks) / frequency) for term, frequency in frequencies.items()}
    for q, prompt, answer, detail in rows(exam):
        base = tokens(q["instruction"])
        specific = tokens(detail)
        supporting = set()
        if q["type"] == "ce":
            supporting = tokens(" ".join(p["text"] for p in q["parts"] if p["correct"] == "C"))
        query = base | specific | supporting
        detail_words = normalized(detail).split()
        weighted = {word: idf.get(word, 2) * (1.8 if word in base else 1.4 if word in specific else 0.65) for word in query}
        ranked = []
        for chunk in chunks:
            matched = chunk["terms"] & query
            score = sum(weighted[word] for word in matched)
            # Um tópico do enunciado deve aparecer; a alternativa isolada pode ser um distrator.
            coverage = len(chunk["terms"] & (base | specific)) / max(1, len(base | specific))
            score *= 0.6 + coverage
            phrase = difflib.SequenceMatcher(None, detail_words, chunk["words"], autojunk=False).find_longest_match().size
            score += min(phrase, 20) ** 1.7
            ranked.append((score, coverage, len(chunk['terms'] & (base | specific)), phrase, chunk))
        ranked.sort(key=lambda row: row[0], reverse=True)
        best = []
        used_pages = set()
        for score, coverage, matched, phrase, chunk in ranked:
            if chunk["page"] in used_pages:
                continue
            used_pages.add(chunk["page"])
            best.append({"page": chunk["page"], "text": chunk["text"], "score": round(score, 2), "coverage": round(coverage, 2), "matched": matched, "phrase": phrase})
            if len(best) == 3:
                break
        yield {"book": exam["num"], "question": q["n"], "prompt": prompt, "answer": answer, "candidates": best}


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("source", type=Path)
    parser.add_argument("--cache", type=Path, default=Path("storage/app/ibadeg/book-pages.json"))
    parser.add_argument("--report", type=Path, default=Path("storage/app/ibadeg/excerpt-candidates.json"))
    parser.add_argument("--publish", action="store_true")
    parser.add_argument("--reuse-report", action="store_true")
    parser.add_argument("--output", type=Path, default=Path("database/data/ibadeg-book-excerpts.json"))
    parser.add_argument("--overrides", type=Path, default=Path("database/data/ibadeg-excerpt-overrides.json"))
    parser.add_argument("--ocr-cache", type=Path, default=Path("storage/app/ibadeg/homiletica-pages.json"))
    args = parser.parse_args()
    if args.cache.exists():
        books = json.loads(args.cache.read_text(encoding="utf-8"))
    else:
        from pypdf import PdfReader
        books = {p.name[:2]: {"file": p.name, "pages": [page.extract_text() or "" for page in PdfReader(p).pages]} for p in sorted((args.source / "LIVROS").glob("*.pdf"))}
        args.cache.parent.mkdir(parents=True, exist_ok=True)
        args.cache.write_text(json.dumps(books, ensure_ascii=False), encoding="utf-8")
    if args.ocr_cache.exists():
        ocr_pages = json.loads(args.ocr_cache.read_text(encoding='utf-8'))
        if len(ocr_pages) != len(books['19']['pages']):
            raise ValueError('O OCR de Homilética não contém todas as páginas do PDF.')
        books['19']['pages'] = ocr_pages
        books['19']['ocr'] = True
    html = (args.source / "SITE PROVAS/index.html").read_text(encoding="utf-8")
    assignment = re.search(r"const\s+EXAMS\s*=\s*", html)
    exams, _ = json.JSONDecoder().raw_decode(html[assignment.end():])
    results = json.loads(args.report.read_text(encoding="utf-8")) if args.reuse_report else [row for exam in exams for row in candidates(exam, books[exam["num"]])]
    args.report.parent.mkdir(parents=True, exist_ok=True)
    args.report.write_text(json.dumps(results, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"{len(results)} itens analisados. Candidatos para revisão: {args.report}")
    if args.publish:
        overrides = json.loads(args.overrides.read_text(encoding='utf-8')) if args.overrides.exists() else {}
        index = {'version': 1, 'books': {}}
        count = 0
        for num, book in books.items():
            source_pdf = args.source/'LIVROS'/book['file']
            index['books'][num] = {'title': Path(book['file']).stem, 'sha256': hashlib.sha256(source_pdf.read_bytes()).hexdigest(), 'page_count': len(book['pages']), 'ocr': book.get('ocr', False), 'questions': {}, 'passages': {}}
        for row in results:
            book = index['books'][row['book']]
            key = hashlib.sha256((row['prompt']+'\0'+row['answer']).encode('utf-8')).hexdigest()
            label = re.search(r' — ([a-z])\)', row['prompt'])
            override = overrides.get(f"{row['book']}:{row['question']}:{label[1]}" if label else '')
            if override is None:
                override = overrides.get(f"{row['book']}:{row['question']}")
            if override is False:
                continue
            if override:
                fragments = []
                pages = []
                for fragment in override['fragments']:
                    page = fragment['page']
                    raw = re.sub(r'\s+', ' ', clean_page(books[row['book']]['pages'][page-1]))
                    start = raw.find(fragment['start'])
                    if start < 0:
                        raise ValueError(f"Âncora não encontrada {row['book']}:{row['question']}, p.{page}: {fragment['start']}")
                    end = raw.find(fragment['end'], start) if fragment.get('end') else start + fragment.get('length', 500)
                    if end < start:
                        raise ValueError('Fim do trecho não encontrado: '+str(fragment))
                    if fragment.get('include_end'):
                        end += len(fragment['end'])
                    fragments.append(raw[start:end].strip())
                    pages.append(page)
                chosen = {'text': ' […] '.join(fragments), 'page': str(min(pages)) if min(pages)==max(pages) else f'{min(pages)}–{max(pages)}'}
            else:
                if not row['candidates']:
                    continue
                candidate = row['candidates'][0]
                # Correspondências apenas temáticas ou distratores não são publicadas.
                accepted = (candidate['matched'] >= 3 and candidate['coverage'] >= .6) or (candidate['phrase'] >= 6 and candidate['matched'] >= 2)
                if not accepted:
                    continue
                chosen = {'text': '… '+candidate['text']+' …', 'page': str(candidate['page'])}
            passage_key = hashlib.sha256((chosen['page']+'\0'+chosen['text']).encode('utf-8')).hexdigest()[:20]
            book['passages'][passage_key] = chosen
            book['questions'][key] = passage_key
            count += 1
        args.output.parent.mkdir(parents=True, exist_ok=True)
        args.output.write_text(json.dumps(index, ensure_ascii=False, indent=2)+'\n',encoding='utf-8')
        print(f'{count} associações com trechos publicadas; {len(results)-count} itens sem associação segura.')


if __name__ == "__main__":
    main()
