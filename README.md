# Post types creator
Helper plugin that provides an easy interface for creating fully translated custom post types and taxonomies according to best practice with only a few lines of code for WordPress.

Current version need Advanced Custom Fields to be installed for sorting to work.

##Features
- Easy interface, just a few lines of code requiered.
- Flexible. Does not restrict anything, you can pass any arguments to ` register_post_type() ` and ` register_taxonomy() ` by simply specifying them in the normal way and they will override the defaults. 
- Translation ready (All the labels in WP Admin will be translated and you only need to specify singular and plural form of the post type name). Translation currently supports the following languages:
  - English
  - Swedish
  - Norwegian
- Custom columns in admin with only a few lines of code
- Drag & drop sortable (drag and drop in the normal list view in WP Admin for both posts and taxonomies/terms)


##Install and Usage

###Composer
Add the repository and add ` pelmered/post-types-creator ` to the require section in your composer.json. Example of typical full composer.json file:

```
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/pelmered/post-types-creator"
    }
  ],
  "require": {
    "pelmered/post-types-creator": "dev-master"
  },
  "extra": {
    "wordpress-install-dir": "public/wp",
    "installer-paths": {
        "public/wp-content/plugins/{$name}/": ["type:wordpress-plugin"]
    }
  }
}
```

###Normal manual install
First, install the plugin as usnual by uploading the plugin to you plugins folder, typically ` wp-content/plugins/  `.

Secondly, copy the example plugin file from ` example/example-plugin.php ` in this plugin to your plugins folder, typically ` wp-content/plugins/  ` and change the name and edit the data acording to your needs.

###Usage

####Adding custom post types

#####Minimal:
Registers a post type with the slug ` stores ` and the labels tranlatable based on ` Post type plural ` (plural) and ` Post type singular ` (singular).

```php
$ptc = new PE_Post_Type_Creator();
$text_domain = 'text-domain';
        
$ptc->set_post_types(array(
    'stores' => array(
        'singular_label' => _x('store', 'Post type plural', $text_domain),
        'plural_label'  => _x('stores', 'Post type singular', $text_domain)
    )
));

add_action( 'init', array($ptc, 'init'), 0 );
```

#####Example / typical:
Same as minimal, but allso adds a description, makes it drag and drop sortable in the admin list, adds a custom admin column and overides some ` register_post_type() ` defaults, for example connecting the taxonomy ` area `(see example below).

```php
$ptc = new PE_Post_Type_Creator();
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

####Adding taxonomies:
Typical taxonomy that is drag and drop sortable in the normal admin list view and connected to the ` stores ` post type in the example above.

```php
$ptc = new PE_Post_Type_Creator();
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
```

For more examples, or help to get started see the example plugin in ` example/example-plugin.php `. Copy the example plugin to your plugins directory for the fastest way to get started.
