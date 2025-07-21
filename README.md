# Nextcloud Full Text Search - SQL Platform

This is an extension to the *Full text search*  framework.

It allows you to index your content into the usual Nextcloud database.

## Compatibility

The extension requires your Nextcloud database to be MySQL (tested) or PostgreSQL (currently untested). SQLite might work as well, but isn't yet implemented.

## Status

This is currently just a proof of concept. I just wanted to find out why Nextcloud enforces the use of additional components.

What works:
* Indexing of plain text
* Indexing of text in PDF documents
    * This is done by extracting the text via [Smalot/PdfParser].
    * This app itself does *NOT* do optical chracter recognition (OCR)! If your files don't already contain the extracted text, maybe the [files_fulltextsearch_tesseract] app is for you. I haven't tested it together with this app.
* MySQL
* Basic searching
    * If the database is MySQL, it uses [Boolean Full-Text Searches], so you can use operators like `+`  and `-`, as well as a trailing `*` wildcard

[Smalot/PdfParser]: https://github.com/Smalot/PdfParser
[files_fulltextsearch_tesseract]: https://github.com/nextcloud/files_fulltextsearch_tesseract

What does *NOT* work:
* Access control? The framework delivers access details about indexed documents as well as information about the viewer on search requests, but I think their purpose is to *optionally* increase search performance. Access control seems to happen in the content provider, as in my tests only legitimate users were able to find files.
* Indexing of Office documents: The upstream [fulltextsearch_elasticsearch] app simply passes the files on to the [Elasticsearch Attachment processor], which in turn uses [Apache Tika] for processing. Since I want to keep this app lean, I don't want to pull in any Java dependencies.
* "Advanced" features of the full text search framework. There are fields for tags, metatags, subtags, parts, excerpts and whatnot. I have no idea yet what they are used for. The app just stores them on indexing and returns them in search results, but doesn't search those fields.
* PostgreSQL: Could work, but I haven't tested it. Might need small fixes, and plainly assumes "english" configuration (which influences stopwords and normalization).
* SQLite: Might be implementable, but I haven't spent more time than a quick search for "fulltext search sqlite"

[fulltextsearch_elasticsearch]: https://github.com/nextcloud/fulltextsearch_elasticsearch
[Boolean Full-Text Searches]: https://dev.mysql.com/doc/refman/8.4/en/fulltext-boolean.html
[Elasticsearch Attachment processor]: https://www.elastic.co/docs/reference/enrich-processor/attachment
[Apache Tika]: https://tika.apache.org/