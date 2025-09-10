# Nextcloud Full Text Search - SQL Platform

This is an extension to the *Full text search*  framework.

It allows you to index your content into the usual Nextcloud database.

**Warning:** This app will store all indexed content in your Nextcloud database *twice* (roughly) - once as plain text and once as searchable index. This means that your database can easily grow hundreds of megabytes or even gigabytes in size if you have much indexable content (e.g. documents).

## Compatibility

The extension requires your Nextcloud database to be MySQL or PostgreSQL.

## Status

This is currently just a proof of concept. I just wanted to find out why Nextcloud enforces the use of additional components.

What works:
* Indexing of plain text
* Indexing of text in PDF documents
    * This is done by extracting the text via [Smalot/PdfParser].
    * This app itself does *NOT* do optical chracter recognition (OCR)! If your files don't already contain the extracted text, maybe the [files_fulltextsearch_tesseract] app is for you. I haven't tested it together with this app.
* MySQL (tested in CI pipeline and in real world usage)
* PostgreSQL (tested in CI pipeline)
    * Plainly assumes "english" configuration (which influences stopwords and normalization)
* Basic searching
    * If the database is MySQL, it uses [Boolean Full-Text Searches], so you can use operators like `+` and `-`, as well as a trailing `*` wildcard
    * If the database is PostgreSQL, the query is converted using [`websearch_to_tsquery`], so you can use `-` for exclusions and quote text to enforce word groups
* Passing the `occ fulltextsearch:test` harness

[Smalot/PdfParser]: https://github.com/Smalot/PdfParser
[files_fulltextsearch_tesseract]: https://github.com/nextcloud/files_fulltextsearch_tesseract
[Boolean Full-Text Searches]: https://dev.mysql.com/doc/refman/8.4/en/fulltext-boolean.html
[`websearch_to_tsquery`]: https://www.postgresql.org/docs/current/textsearch-controls.html#TEXTSEARCH-PARSING-QUERIES

What does *NOT* work:
* Indexing of Office documents: The upstream [fulltextsearch_elasticsearch] app simply passes the files on to the [Elasticsearch Attachment processor], which in turn uses [Apache Tika] for processing. Since I want to keep this app lean, I don't want to pull in any Java dependencies.
* "Advanced" features of the full text search framework. There are fields for tags, metatags, subtags, parts and whatnot. I have no idea yet what they are used for. The app just stores them on indexing and returns them in search results, but doesn't search those fields.
* SQLite: Might be implementable, but I haven't spent more time than a quick search for "fulltext search sqlite"

[fulltextsearch_elasticsearch]: https://github.com/nextcloud/fulltextsearch_elasticsearch
[Elasticsearch Attachment processor]: https://www.elastic.co/docs/reference/enrich-processor/attachment
[Apache Tika]: https://tika.apache.org/