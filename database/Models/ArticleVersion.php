<?php

namespace MagicProDatabaseModels; // в композере прописывается

use Illuminate\Database\Eloquent\Model;

/**
 * One saved state of an article: `magicPro_article_versions`.
 *
 * Written by MagicProSrc\Archive\ArticleArchive on every save that goes through
 * the articles api, and never touched again — hence `created_at` alone and no
 * `updated_at`.
 *
 * `data` is the whole article as json, plain text and not compressed. The
 * article is kept in one piece and not in columns on purpose: the archive must
 * not know what an article is made of, or every new field would need a
 * migration here and the versions written before it would stop being readable.
 *
 * `articleId` is a plain number without a foreign key. Versions of a deleted
 * article stay: they are the only way back, and a key with a cascade would take
 * exactly them away at the worst moment.
 */
class ArticleVersion extends Model
{
    protected $table = 'magicPro_article_versions';

    public const UPDATED_AT = null;

    protected $fillable = [
        'userId',
        'articleId',
        'data',
    ];

    protected $casts = [
        'userId'     => 'integer',
        'articleId'  => 'integer',
        'created_at' => 'datetime',
    ];
}
