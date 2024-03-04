# Introduction

Relationships are links between two associated tables. You can access relationships like normal members and easily
obtain model instances in those relationships.

## Defining relationships

### One to one

These are the most basic relationships. For example, one of our `User` models could be associated with one `Avatar`
model.

To establish this relationship, the model which owns the other should use a method called `hasOne`. In this case, our
user owns the avatar.

```php title="App\Models\User"
class User extends Model {

	public function avatar() {
		return $this->hasOne('App\Models\Avatar');
	}

}
```

Now we also want to define this relationship on the avatar's side, so we can easily access the user that owns it. The
appropriate method for this is `belongsTo`.

```php title="App\Models\Avatar"
class Avatar extends Model {

	public function user() {
		return $this->belongsTo('App\Models\User');
	}

}
```

### One to many

These relationships connect a single model to any amount of another model. For example, a user might have made multiple
blog posts, but each blog post only belongs to one user.

This works very similarly to one-to-one relationships, except you should use `oneToMany`.

```php title="App\Models\User"
class User extends Model {

	public function posts() {
		return $this->hasMany('App\Models\Post');
	}

}
```

With the relationship defined, we're able to access the user's posts as an array simply by calling the `posts` property.
Horizon will internally recognize the relationship and fetch the posts.

```php
$posts = $user->posts();

foreach ($posts as $post) {
    $postId = $post->id;
}
```

Of course, the inverse of the `hasMany` method is still `belongsTo`, since each post only belongs to a single user.
Be sure to set that properly on the post's model.

### Many to many

These relationships are more complicated than one-to-one or one-to-many, and will require a third table to act as a link
between the two tables.

For example, say we assigned our users to roles (editor, moderator, administrator). Each user can have multiple roles,
but each role can also have multiple users.

To make this work, you'll need to create a link table. If you're using migrations,
[they can do this for you](../database/migrations.md#many-to-many). Here's a basic link table schema. Note that the name
of the link table puts the two model names into alphabetical order.

```sql
+---------------------------------------+
| Table `post_user`                     |
+-----------+-------------+-------------+
| `id`      | INTEGER(11) | PRIMARY KEY |
| `user_id` | INTEGER(11) | INDEX       |
| `post_id` | INTEGER(11) | INDEX       |
+-----------+-------------+-------------+
```

Like in a one-to-many relationship, our user will still use `hasMany`.

```php title="App\Models\User"
class User extends Model {

	public function posts() {
		return $this->hasMany('App\Models\Post');
	}

}
```

The difference is in the role model, which now uses `belongsToMany`.

```php title="App\Models\Role"
class Role extends Model {

	public function users() {
		return $this->belongsToMany('App\Models\User');
	}

}
```

## Querying relationships

In the above examples, we used relationships as a property. But Horizon also allows you to use them as a method, and
obtain a query builder for that relationship.

For instance, consider our user-posts example.

```php title="App\Models\User"
class User extends Model {

	public function posts() {
		return $this->hasMany('App\Models\Post');
	}

}
```

We can call `posts()` and filter through the user's posts.

```php
$user = User::find(1);
$recentPosts = $user->posts()
    ->where('posted_at', '>', time() - 86400)
    ->get();
```
