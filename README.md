# ACF Agency Workflow

## Ideals

### Fields are stored with themes and plugins.

You already separate your website's looks from it's functionality. You should do the same with your custom fields. For example: Store the header settings with your theme, the client's address fields with their core functionality plugin, and those API settings with your API plugin.

### Fields are in your Git repo.

You collaborate using git. So we've made the ACF JSON *cache* the source of truth. How? Your JSON files get forcefully synced to your database when an administrator loads the Dashboard or Field Groups page. If you've added, modified or deleted a field, then you have something to commit.

## For your information

- You can safely move existing Field Groups to a new location by editing them and setting a new JSON Save path.
- Do not rename Field Group JSON files. For better performance this plugin grabs the Key from the filename, not from the inside of the file. The Key inside `group_5e287670568af.json` is *group_5e287670568af*, by default. Traditionally you could rename the JSON files and the Field Groups would still work.
- Remove and stop using `"private": true,` in your JSON. It will break this plugin. For the same-ish result, we hide the menu link instead.
- There is no Field Group Trash anymore. The Field Group Trash link has been replaced with a Delete Permanently link. Traditional Trash has no JSON file, so it would get purged from the database by the sync functionality. To avoid confusion about this, Trash has been replaced with a Delete link.
- The Field Groups backend *menu link* has been removed on non-local environments to deter use. The page is still available however, if you visit the URL directly.

## Usage examples

Coming later.

## Requirements

This plugin was developed with:

- PHP 7.3
- WordPress 5.3.2
- Advanced Custom Fields PRO 5.8.7

Requirements may be lowered after proper testing.

## Installation

1. Back up any existing Field Groups as a precaution.
1. Define `WP_ENV` as `development` on your local environment.
1. Download & activate ACF PRO.
1. Download & activate ACF Agency Workflow.
1. Set ACF local JSON load locations.

## Set an ACF local JSON location in a theme

Create an `acf-json` folder in your theme.

## Set an ACF local JSON location in a child theme

Create an `acf-json` folder in your child theme.

## Set an ACF local JSON location in a parent theme

Create an `acf-json` folder in your parent theme.
Add this code to your parent theme's `functions.php`.

```
/**
 * ACF Local JSON location for a parent theme.
 */

add_filter( 'acf/settings/load_json', function ( $paths ) {
    $paths[] = get_template_directory().'/acf-json';
    return $paths;
});
```

## Set an ACF local JSON location in a plugin

Create an `acf-json` folder in your plugin.
Add this code to your plugin's main file. (`plugin-name/plugin-name.php`)

```
/**
 * ACF local JSON location for a plugin.
 */

add_filter( 'acf/settings/load_json', function ( $paths ) {
    $paths[] = plugin_dir_path( __FILE__ ).'acf-json';
    return $paths;
});
```

## Contributing

Found a bug? Anything you'd like to ask, add, change, or have added or changed? Please open an issue so we can talk about it.

## Disclaimer

The author(s) are not responsible for lost fields or other data. This plugin deletes things, so it can be dangerous. Backups are a good idea. Git can be your backup too.

## License

[MIT](/LICENSE) &copy; [Tim Brugman](https://timbr.dev/)