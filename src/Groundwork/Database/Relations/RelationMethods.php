<?php

namespace Groundwork\Database\Relations;

trait RelationMethods
{
    public array $relations = [];

    /**
     * Links a single model to this model in a 1:1 relation.
     *
     * For example; if THIS model is a 'User', you can get its 'Profile' by using:
     *
     * ```
     * public function profile() {
     *     return $this->hasOne(Profile::class, 'profile_id');
     *                   Target class -^             ^
     *                    Our column with Their ID -/
     * }
     * ```
     *
     * @param string      $model      The ::class of the foreign model. (e.g. User::class)
     * @param string      $foreignKey The key in this model that points to the foreign model. (e.g. 'user_id')
     * @param string|null $localKey   The key in the foreign model that's used to identify itself with (usually 'id')
     *
     * @return HasOne
     */
    protected function hasOne(string $model, string $foreignKey, string $localKey = null) : HasOne
    {
        return new HasOne($this, $model, $foreignKey, $localKey);
    }

    /**
     * Links multiple foreign models to this model in a 1:many relation.
     *
     * For example; if THIS model is a 'User', you can get all the user's 'Posts' by using:
     *
     * ```
     * public function posts() {
     *     return $this->hasMany(Post::class, 'user_id');
     *                  Their class -^            ^- Their column that points to THIS class
     * }
     * ```
     *
     * @param string      $model      The ::class of the foreign model. (e.g. Post::class)
     * @param string      $foreignKey The key the foreign model that references this model. (e.g. 'user_id')
     * @param string|null $localKey   The key in this model that's used to link to the foreign model (usually 'id')
     *
     * @return HasMany
     */
    protected function hasMany(string $model, string $foreignKey, string $localKey = null): HasMany
    {
        return new HasMany($this, $model, $foreignKey, $localKey);
    }

    /**
     * Gets the Model that this model belongs to in a many:1 relation.
     *
     * For example; if THIS model is a 'Post', you can get its author by using:
     *
     * ```
     * public function author() {
     *     return $this->belongsTo(User::class, 'author_id');
     *                    Their class -^             ^- Our column with Their ID
     * }
     * ```
     *
     * @param string      $model      The ::class of the foreign model. (e.g. User::class)
     * @param string      $foreignKey The key in this model that points to the foreign model. (e.g. 'user_id')
     * @param string|null $ownerKey   The key in the foreign model that's used to identify itself with (usually 'id')
     *
     * @return BelongsTo
     */
    protected function belongsTo(string $model, string $foreignKey, string $ownerKey = null) : BelongsTo
    {
        return new BelongsTo($this, $model, $foreignKey, $ownerKey);
    }

    /**
     * Gets the `$related` model through another `$through` model. While THIS model and the `$related` model do not have
     * any direct connections, the common ground would be found in the `$through` model. In order for this to work, the
     * following requirements must be met:
     * * The `$through` model needs to have a key that references THIS model.
     *   (known as `$firstForeign` on `$through` and `$firstOwner` on THIS)
     * * The `$related` model needs to have a key that references the `$through` model.
     *   (known as `$secondForeign` on `$related` and `$secondOwner` on `$through`)
     *
     * For example; if THIS model is a `Login`, you can get the `Profile` of the `User` using:
     *
     * ```
     * public function profile() {
     *     return $this->hasOneThrough(Profile::class, User::class, 'last_login_id', 'user_id');
     *                          Target class -^            ^               ^              ^
     *                                     Related class -/                |              |
     *                                               Foreign key on User -/               |
     *                                                           Foreign key on Profile -/
     * }
     * ```
     *
     * @param string      $related          The model to acquire
     * @param string      $through          The model through which we can find the target model
     * @param string      $firstForeign     The key in the $through model that references our model
     * @param string      $secondForeign    The key in the $related model that references the $though model
     * @param string|null $firstOwner       The identifier key in our model that's referenced by $through
     * @param string|null $secondOwner      The identifier key in the $through model that's referenced by $related
     *
     * @return HasOneThrough
     */
    public function hasOneThrough(string $related, string $through, string $firstForeign, string $secondForeign, string $firstOwner = null, string $secondOwner = null) : HasOneThrough
    {
        return new HasOneThrough($this, $related, $through, $firstForeign, $secondForeign, $firstOwner, $secondOwner);
    }

    /**
     * Gets the `$related` models through other `$through` models. While THIS model and the `$related` models do not have
     * any direct connections, the common ground would be found in the `$through` models. In order for this to work, the
     * following requirements must be met:
     * * The `$through` model needs to have a key that references THIS model.
     *   (known as `$firstForeign` on `$through` and `$firstOwner` on THIS)
     * * The `$related` model needs to have a key that references the `$through` model.
     *   (known as `$secondForeign` on `$related` and `$secondOwner` on `$through`)
     *
     * For example; if THIS model is a `User`, you can get the `Comments` of their `Posts` using:
     *
     * ```
     * public function receivedComments() {
     *     return $this->hasOneThrough(Comment::class, Post::class, 'author_id', 'post_id');
     *                          Target model -^            ^               ^          ^
     *                                     Related class -/                |          |
     *                                              Foreign key on Posts -/           |
     *                                                      Foreign key on Comments -/
     * }
     * ```
     *
     * @param string      $related          The models to acquire
     * @param string      $through          The models through which we can find the target models
     * @param string      $firstForeign     The key in the $through model that references our model
     * @param string      $secondForeign    The key in the $related model that references the $through model
     * @param string|null $firstOwner       The identifier key in our model that's referenced by $through
     * @param string|null $secondOwner      The identifier key in the $through model that's referenced by $related
     *
     * @return HasManyThrough
     */
    public function hasManyThrough(string $related, string $through, string $firstForeign, string $secondForeign, string $firstOwner = null, string $secondOwner = null) : HasManyThrough
    {
        return new HasManyThrough($this, $related, $through, $firstForeign, $secondForeign, $firstOwner, $secondOwner);
    }

    /**
     * Allows a model to be linked to multiple other models through an intermediate table in a many:many relation.
     *
     * For example; if THIS model is a `Role`, you can get the `Users`, as a role can be assigned to multiple users and
     * a user can have multiple roles, using:
     *
     * ```
     * public function users() {
     *     return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id');
     *                       Target model -^             ^             ^          ^
     *                              Intermediate table -/              |          |
     *       Column in intermediate table that references THIS model -/           |
     *            Column in intermediate table that references the target model -/
     * }
     * ```
     *
     * @param string $model         The model to acquire
     * @param string $intermediate  The intermediate table that links both models together
     * @param string $localKey      The column in the intermediate table that references our model
     * @param string $foreignKey    The column in the intermediate table that references the target model
     *
     * @return BelongsToMany
     */
    public function belongsToMany(string $model, string $intermediate, string $localKey, string $foreignKey) : BelongsToMany
    {
        return new BelongsToMany($this, $model, $intermediate, $localKey, $foreignKey);
    }

    /**
     * Attempts to load the model saved to the morph key. It assumes the key name appended with _type and _id exist.
     *
     * For example; If THIS model is a `HeaderImage`, you can get the `Post` or the `Profile` it was placed on using:
     *
     * ```
     * public function imageable() {
     *     return $this->morphTo('imageable'); // optionally you can use __FUNCTION__ instead.
     *                               ^- The column name that has_type and _id to reference
     *                                  the model type and ID
     * }
     * ```
     *
     * In such example, a `HeaderImage` may have `imageable_type` set to 'App/Model/Profile' and `imageable_id` to 26
     *
     * @param string $morphKey  The polymorphic column name
     *
     * @return MorphTo
     */
    public function morphTo(string $morphKey) : MorphTo
    {
        return new MorphTo($this, $morphKey);
    }

    /**
     * Fetches a model that could reference this model. The referenced model would have a morphKey appended with _type
     * and _id, which should reference the current model. This is a 1:1 relationship (it only gets one model).
     *
     * For example; if THIS model is a `Post`, you can get a `HeaderImage` using:
     *
     * ```
     * public function headerImage() {
     *     return $this->morphOne(HeaderImage::class, 'imageable');
     *                     Target class -^                 ^
     *                              Polymorphic key name -/
     * }
     * ```
     *
     * @param string $model     The model to fetch
     * @param string $morphKey  The polymorphic column name
     *
     * @return MorphOne
     */
    public function morphOne(string $model, string $morphKey) : MorphOne
    {
        return new MorphOne($this, $model, $morphKey);
    }

    /**
     * Fetches all models that references this model in their morph configuration. The referenced models would have a
     * morphKey appended with _type and _id, which would reference the current model. This is a 1:many relationship.
     *
     * For example; if THIS model is a `Post`, you can get all its `Comments` using:
     *
     * ```
     * public function comments() {
     *     return $this->morphMany(Comment::class, 'commentable');
     *                      Target class -^              ^
     *                            Polymorphic key name -/
     * }
     * ```
     *
     * @param string $model     The target model to fetch
     * @param string $morphKey  The polymorphic column name
     *
     * @return MorphMany
     */
    public function morphMany(string $model, string $morphKey) : MorphMany
    {
        return new MorphMany($this, $model, $morphKey);
    }

    /**
     * Fetches all models that are referenced in an intermediate table which contains a polymorphic relationship. This
     * is a many:many relationship.
     *
     * For example; if THIS model is a `Post`, we can get all `Tags` linked to this post which may also be linked to
     * other models, using:
     *
     * ```
     * public function tags() {
     *     return $this->morphToMany(Tag::class, 'tag_id', 'tagable', 'tagables');
     *                    Target class -^            ^         ^          ^
     *   Key in intermediate table that references -/          |          |
     *   the target.                                           |          |
     *                 Polymorphic key in intermediate table -/           |
     *                                          Intermediate table name -/
     * }
     * ```
     *
     * @param string      $model        The target model to fetch
     * @param string      $modelKey     The column in the intermediate table that references the target model
     * @param string      $morphKey     The polymorphic column in the intermediate table that references our model
     * @param string|null $intermediate The intermediate table name
     *
     * @return MorphToMany
     */
    public function morphToMany(string $model, string $modelKey, string $morphKey, string $intermediate = null) : MorphToMany
    {
        return new MorphToMany($this, $model, $modelKey, $morphKey, $intermediate ?? $morphKey . 's');
    }

    /**
     * Fetches all models that are morphed through an intermediate table and are referenced by this model. This is an
     * inverse of a many:many relationship.
     *
     * For example; if THIS model is a `Tag`, we can get all `Post` models that are linked, using:
     *
     * ```
     * public function posts() {
     *     return $this->morphedByMany(Post::class, 'tag_id', 'tagable', 'tagables');
     *                       Target class -^           ^         ^           ^
     *             Column in intermediate table that -/          |           |
     *             references our model (Tag)                    |           |
     *        The polymorphic column in the intermediate table -/            |
     *                                         The intermediate table name -/
     * }
     * ```
     *
     * @param string      $model        The target model to fetch
     * @param string      $localKey     The column in the intermediate table that references our model
     * @param string      $morphKey     The polymorphic column in the intermediate table that references the target model
     * @param string|null $intermediate The intermediate table name
     *
     * @return MorphedByMany
     */
    public function morphedByMany(string $model, string $localKey, string $morphKey, string $intermediate = null) : MorphedByMany
    {
        return new MorphedByMany($this, $model, $localKey, $morphKey, $intermediate ?? $morphKey . 's');
    }

}