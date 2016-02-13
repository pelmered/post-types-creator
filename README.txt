=== Plugin Name ===
Contributors: pekz0r
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8L2PHLURJMC8Y
Tags: custom post type, cpt, custom taxonomy, sortable posts, admin columns
Requires at least: 4.0
Tested up to: 4.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Lightweight helper plugin that makes it easy to create fully translated custom post types and taxonomies with only a few lines of code.

== Description ==

This is a lightweight helper plugin that provides an easy interface for creating fully translated custom post types
and taxonomies according to best practice with only a few lines of code for WordPress.

There is no GUI at all for creating post types or taxonomies in order to keep the plugin fast and lightweight.
You need to write a few lines of code yourself to register the post types, but but don't worry it's much easier and
faster than the normal way.

= Features =
* Easy interface, just a few lines of code requiered.
* Flexible. Does not restrict anything, you can pass any arguments to ` register_post_type() ` and ` register_taxonomy() ` by simply specifying them in the normal way and they will override the defaults.
* Translation ready (All the labels in WP Admin will be translated and you only need to specify singular and plural form of the post type name). Translation currently supports the following languages:
  * English
  * Swedish
  * Norwegian
  * Place help me to add more!
* Translated permalinks/slugs for post types and taxonomies are generated automatically, but it is possible to override this deafault.
* Custom columns in admin with only a few lines of code
* easily add custom post statuses
* Drag & drop sortable (drag and drop in the normal list view in WP Admin for both posts and taxonomies/terms)
* Integration with Advanced Custom Fields(optional)

= Planned features =
* Unit tests
* Use new merm meta functionality for sorting for WordPress 4.4+

= Usage =

Check out the [instructions](https://wordpress.org/plugins/post-types-creator/how-to-use-the-plugin/ "How to use Post Types Creator").

For more detailed documentation. Check the [Wiki](https://github.com/pelmered/post-types-creator/wiki "Post Type Creator Wiki").

== How to use the plugin ==

= Adding custom post types =

**Minimal**

Registers a post type with the slug ` stores ` and the labels tranlatable based on ` Post type plural ` (plural) and ` Post type singular ` (singular).

`
$options = array(
  // Use ACF for storing meta data
  'use_acf' => true
);

$ptc = new Post_Type_Creator();
$text_domain = 'text-domain';

$ptc->set_post_types(array(
    'stores' => array(
        'singular_label' => _x('store', 'Post type plural', $text_domain),
        'plural_label'  => _x('stores', 'Post type singular', $text_domain)
    )
));

add_action( 'init', array($ptc, 'init'), 0 );
`

*Example / typical*
Same as minimal, but allso adds a description, makes it drag and drop sortable in the admin list, adds a custom admin column and overides some ` register_post_type() ` defaults, for example connecting the taxonomy ` area `(see example below).

```php
$ptc = new Post_Type_Creator();
$text_domain = 'text-domain';

$ptc->set_post_types(array(
    'stores' => array(
        'singular_label' => _x('store', 'Post type plural', $text_domain),
        'plural_label'  => _x('stores', 'Post type singular', $text_domain),
        'description'   => _x('All company stores', 'Post type description', $text_domain),

        // Make post type drag and drop sortable in admin list view (default: false)
        'sortable'      => true,
        'admin_columns' => array(
            'image' => array(
                //Column header/label
                'label' => 'Image',
                //In what position should the column be (optional)
                'location'  => 2,
                //callback for column content. Arguments: $post_id
                'cb'    => 'example_get_featured_image_column'
            )
        )

        // Override any defaults from register_post_type()
        // http://codex.wordpress.org/Function_Reference/register_post_type
        'supports'            => array( 'title', 'editor', 'thumbnail',),
        'taxonomies'          => array( 'area' ),
    )
));

add_action( 'init', array($ptc, 'init'), 0 );

function example_get_featured_image_column( $post_id )
{
    echo get_the_post_thumbnail( $post_id, 'thumbnail' );
}
```

**Adding taxonomies**
Typical taxonomy that is drag and drop sortable in the normal admin list view and connected to the ` stores ` post type in the example above.

`php
$ptc = new Post_Type_Creator();
$text_domain = 'text-domain';

$ptc->set_taxonomies(array(
    'area' => array(
        'singular_label' => _x('area', 'Post type plural', $text_domain),
        'plural_label'  => _x('areas', 'Post type singular', $text_domain),
        'description'   => _x('Areas for grouping stores', 'Post type description', $text_domain),
        'post_type'    => 'stores',

        // Make post type drag and drop sortable in admin list view (default: false). Affects all get_terms()-queries
        'sortable'      => true,

        // Override any defaults from register_taxonomy()
        // http://codex.wordpress.org/Function_Reference/register_taxonomy

    )
));

add_action( 'init', array($ptc, 'init'), 0 );
`

** More examples / Example plugin **
For more examples, or help to get started see the example plugin in ` example-plugin/my-custom-post-types.php `. Copy the example plugin to your plugins directory for the fastest way to get started.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/post-types-creator` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Copy /wp-content/plugins/post-types-creator/example-plugin/my-custom-post-types/ into /wp-content/plugins/ and rename the folder to fit your installation. For example "<site name>-post-types"
1. Customize your post types plugin to your need. Here's the [instructions](https://wordpress.org/plugins/post-types-creator/how-to-use-the-plugin/ "How to use Post Types Creator").
1. Activate your post types plugin and check that it works. The post types and taxonomies should now be added you your WordPress installation.

== Frequently Asked Questions ==

= Do I need to know PHP to use this plugin =

Yes, a basic understanding of PHP and custom post types is required.

== Changelog ==

= 1.0.0 =
* First stable version

== Common problems & troubleshooting ==

The plugin is very simple to use if you know basic PHP.
Most of the problems you are likely to run into is related to WordPress built in permalink cache or messed up or
non-existing meta data for sorting(the posts will not show up in the post linst in wp-admin).
To fix both those problems. Just add `&ptc-reinit=1` to the URL/querystring anywhere in wp-admin to run a force
reinitialization that will fix the problems, for example:

`http://example.com/wp-admin/edit.php?post_type=page&ptc-reinit=1`

