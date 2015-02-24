# Post-types-creator
Helper plugin that provides an easy interface for creating fully translated custom post types and taxonomies according to best practice with only a few lines of code for WordPress .

##Features
- Easy interface, just a few lines of code requiered.
- Flexible. Does not restrict anything, you can pass any arguments to ` register_post_type() ` and ` register_taxonomy() ` by simply specifying them in the normal way and they will override the defaults. 
- Translation ready (All the labels in WP Admin will be translated and you only need to specify singular and plural form of the post type name). Translation currently supports the following languages:
  - English
  - Swedish
  - Norwegian
- Custom columns in admin
- Sortable (drag and drop in the normal post list view in WP Admin)


##Install and Usage
First, install the plugin as usnual by uploading the plugin to you plugins folder, typically ` wp-content/plugins/  `.

Secondly, copy the example plugin file from ` example/example-plugin.php ` in this plugin to your plugins folder, typically ` wp-content/plugins/  ` and change the name and edit the data acording to your needs.

###Usage

####Adding posts:
```php
$ptc = new Pelmered_Post_Type_Creator();
        
$ptc->set_post_types(array(
    'stores' => array(
        'sigular_label' => _x('butikk', 'Post type plural', $text_domain),
        'plural_label'  => _x('butikker', 'Post type sigular', $text_domain),
        'description'   => _x('', 'Post type description', $text_domain),
        
        // Make post type drag and drop sortable in admin list view (default: false)
        'sortable'      => true,
        'admin_columns' => array(
            'slug' => array(
                //Column header/label
                'label' => 'Column header',
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
```php
$ptc = new Pelmered_Post_Type_Creator();

$ptc->set_taxonomies(array(
    'area' => array(
        'sigular_label' => _x('area', 'Post type plural', $text_domain),
        'plural_label'  => _x('areas', 'Post type sigular', $text_domain),
        'description'   => _x('', 'Post type description', $text_domain),
        'post_type'    => 'stores',
        
        
        // Override any defaults from register_taxonomy()
        // http://codex.wordpress.org/Function_Reference/register_taxonomy
        
    )
));

add_action( 'init', array($ptc, 'init'), 0 );
```
