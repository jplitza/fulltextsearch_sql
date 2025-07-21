# Full text search - SQL Platform

This is an extension to the *Full text search*  framework.

It allows you to index your content into the usual Nextcloud database.

## Compatibility

The extension requires your Nextcloud database to be MySQL (tested) or PostgreSQL (currently untested). SQLite might work as well, but isn't yet implemented.

## Status

This is currently just a proof of concept. I just wanted to find out why Nextcloud enforces the use of additional components.

What works:
* Indexing of plain text
* MySQL
* Basic searching
    * If the database is MySQL, it uses [Boolean Full-Text Searches], so you can use operators like `+`  and `-`, as well as a trailing `*` wildcard

What does *NOT* work:
* Access control: I spent exactly zero thoughts about that as of yet! So if your instance has more than one user, don't currently use this extension!
* Indexing of PDF or Office documents: While other apps are responsible for providing the content to be indexed, they seem to simply provide the whole document instead of only text. I need to figure out how to handle that, since the upstream fulltextsearch_elasticsearch app seems to be able to handle those file formats itself (or simply passes them on to Elasticsearch)
* "Advanced" features of the full text search framework. There are fields for tags, metatags, subtags, parts, excerpts and whatnot. I have no idea yet what they are used for.
* PostgreSQL: Could work, but I haven't tested it. Might need small fixes, and plainly assumes "english" configuration (which influences stopwords and normalization).
* SQLite: Might be implementable, but I haven't spent more time than a quick search for "fulltext search sqlite"

[Boolean Full-Text Searches]: https://dev.mysql.com/doc/refman/8.4/en/fulltext-boolean.html