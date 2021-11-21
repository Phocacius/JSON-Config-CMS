# JSON Config CMS
A lightweight CMS making it easy to configure contents via json models

## Prerequisites for building
### SASS
SASS is a precompiler for CSS. You need to install ruby first, then sass (`sudo gem install -n /usr/local/bin sass`).  
Compilation is done via `sass sass/frontend.scss css/style.css` for frontend styles and `sass sass/backend.scss css/backend.css` for backend styles.

### JS
For serverside javascript you need to install node and npm first, then browserify: `npm install -g browserify`  
Then `npm install` the frontend libraries. You can compile the browserify code using `browserify js/main.js -o js/bundle.js`


## Setup

### `config.php`

Create a file named `config.php` in your installation's root directory and set the following constants:

- `DB_HOST`
- `DB_USER`
- `DB_PASSWORD`
- `DB_NAME`
- `BASEURL`: the baseurl within your http server. No trailing slash!
- `DOCUMENT_ROOT`: path where images and json file storing the general storage are saved. Should have a trailing slash. Use an absolute path, you can use the `__DIR__` constant though (e.g. `___DIR__.'/data/`)
- `JSON_DIR`: path where the json configuration files for forms and tables are stored. Should have a trailing slash and contain the directories `forms` and `tables`
- `BACKEND_PREFIX`: The base url for the backend relative to the base url. With leading, but no trailing slash, unless you define it empty, e.g. `'/admin'`, `''`

The following constants are optional:

- `BACKEND_URL`: when using the `ExternalAuthenticator`, define the url to your backend in your config.
- `STORAGE_CLASS_NAME`: When extending the default `Storage` class, e.g. for custom placeholders, create a constant with the class name of your Storage class.
- `TMP_DIR`: directory for temporary files. Default: `DOCUMENT_ROOT/tmp`

### `setup.php`

- Set an authenticator using `Authenticator::set`. There are two pre-configured authenticators:
  - `DbTableAuthenticator`: Authenticate using a table in your database: `Authenticator::set(new DbTableAuthenticator("users")`. See the PHPDoc of the `DbTableAuthenticator` for details on the data model.
  - `ExternalAuthenticator`: Authenticate using a HTTP POST request to an external server: `Authenticator::set(new ExternalAuthenticator($urls)` where the constructor argument is an array with the keys `change_password` and `login` containing urls to the corresponding functions relative to the constant `BACKEND_URL`. The login call is expected to return a 2xx code on success and 4xx on error. The response should be in json and contain a field named `token`.

## Routes
Each route encapsulates one functionality of your website, either frontend or backend. Create a new class in `classes/routes/backend` or `classes/routes/frontend` and inherit from one of:

- `FrontendRoute`
- `BackendFormRoute`
- `BackendTableRoute`

## Configuration JSONs

For all configuration jsons:

- `title` (string): Title displayed as view title
- `fields` (array): Configuration Fields, see below

Additionally, for table configuration (BackendTableRoute):

- `orderBy` (string): column name of the default sort order. Will be ignored if there is a field with type `SortOrder`
- `allowZipUpload` (boolean) [default `false`]: Show an file upload field at the bottom of the table. This can be used e.g. for batch updates. The uploaded zip will be automatically unzipped to a temp folder. Override `processFolder()` to handle the update.
- `zipUploadHint` (string): Instructions shown before the zip upload button so the user knows what structure you expect in the zip file (only relevant when allowZipUpload is true)



### Configuration Fields

For all configuration jsons:

- `name` (string): Unique (globally for forms, locally for tables) name for this field. For tables, it should match the database column name
- `type` (string): Type of this field. See `DataTypeFactory` for a list of all available data types
- `label` (string): Label shown in the backend form for this field. Fields without labels will not be shown in the form and are therefore not editable
- `note` (string): Additional description of the field
- `required` (bool) [default `false`]: Marks the field as mandatory, the form will show an error if the field is empty when trying to save
- `config` (array): associative array, individual to each data type's requirements. Check the data type documentation  
  
Additionally, for fields within tables (BackendTableRoute):

- `labelShort` (string): Label shown in the table header in the overview. Falls back to `label` if not set
- `showInTable` (bool) [default `false`]: If set to true, this field will be displayed in the overview table
- `linkInTable` (bool) [default `false`]: If set to true, this field will link to the edit screen of this table entry
- `userEditable` (bool) [default `false`]: Marks that this field can be edited by regular users, not just by admins. Useful for example for the user table, where a user can change their name and email address, but not their role 

### Data Types

#### Bitmap
Display multiple related checkboxes. The result will be saved into a single integer column in the database using flag notation like in chmod: The first option has the bit value 1, the second option 2, the third option 4 etc.

Required configuration fields:
- `options` (array<string>)

#### Checkbox
Displays a single checkbox. The result will be saved as 1 (set) or 0 (unset)  in the database. When `showInTable` is set to true, the value can be toggled right from the backend table

No additional configuration

####  CodeEditor

Displays a code editor using CodeMirror (https://codemirror.net/index.html). Requires the library to be downloaded into `js/lib/codemirror` and `css/codemirror.css`

Optional configuration fields:
- `rows` (int): number of lines initially displayed, default 5
- `language` (string): mime type of the language that should be entered. Syntax highlighting depends on this setting. Default `application/json`
- `default` (string): default code that will be used when no value has yet been saved

#### File
References a file somewhere on the filesystem.

Required configuration:
- storageDirectory (string) : the directory where the files are going to be stored. Either absolute (when starting with a /) or relative to the root directory of the php installation (where the index.php resides).

Optional configuration:
- keepFileNames (boolean): if set to true, the original file name during upload gets preserved, otherwise a random name is chosen when uploading. Default to true
- allowOverwrite (boolean): if set to true, files with the same filename will be overwritten. If set to false, a timestamp will be added after the filename for disambiguation. Only relevant when [keepFileNames] is set to true. Defaults to false

#### GeoLocation
Allows to enter a geographical location with lat/lon coordinates or by entering a location search
that will be resolved to coordinates by the Google service.
The field is stored as a json with keys `lat` and `lon` and requires a varchar database column.
To select the location on a Google Map, save your api key in the `Storage` under the key `Storage::KEY_MAPS_KEY`

Optional configuration:
- lat (double): initial latitude
- lon (double): initial longitude
- zoom (int): initial zoom level
- address-hint (string): hint for the locaiton search input field

#### Hidden
A field that is not editable in the backend form.
It can be used for fields that are modified from outside this backend or AUTO_INCREMENT fields.
It can be rendered in the backend table, something like this will display the id:

```json
{
"name": "id",
"labelShort": "#",
"type": "hidden"
}
```

#### Image
Allows to upload an image and automatically scales it to customisable sizes

Images will be saved on disk at `[DOCUMENT_ROOT]/img/[size]/[filename].[extension]`
`DOCUMENT_ROOT` is a constant that should be defined in your `config.php`.
`size` is the scaleString that was used to scale this image (see below)
`filename` is the original upload filename followed by an underscore and the upload timestamp (to avoid duplicates)  
The database column should be varchar and contains just filename and extension

Required configuration:
- `sizes` (array<string>): An array of sizes this image should be scaled to.
The following formats are supported:
- `[size]` (e.g. 500): scales the image to a square of `[size]` pixels. If the aspect ratio does not fit,
  the image will be center cropped to fit a square
- `[width]x` (e.g. 500x): scales the image to a width of `[width]` pixels. The height is adjusted to maintain
  the image's aspect ratio
- `x[height]` (e.g. x500): scales the image to a height of `[width]` pixels. The width is adjusted to maintain
  the image's aspect ratio
- `[width]x[height]` (e.g. 500x700): scales the image to a fixed pixel size. If the aspect ratio does not fit,
  the image will be center cropped to fit the given aspect ratio

Note that when changing the configuration, you need to reupload all images, otherwise they won't be available in all sizes

#### Info
Just displays a text in the edit form. Can be used to display more context information.
Does not save anything to the database.
Use either text or html depending on what you want to display

Optional configuration:
- text (string): info text to be displayed as plain text
- html (string): info text to be displayed as html

#### Input
Displays an input field that allows to enter plain text, numbers, emails and so on.

Optional configuration:
- type (string): The input type. See https://www.w3schools.com/tags/tag_input.asp for options. default: text
- default (string): Initial value of this field when no value has been saved yet
- validations (array<string>): Validations/Modifications that will be performed on the value before saving. Currently available modifications:
     - lowercase: will transform all letters to lowercase
     - urlsegment: will replace all characters that are not alphanumeric to underscores except for .-/

see Textarea for multiline text input  
see WYSIWYG for formatted text input  

#### ParentId

Use this field if your table allows a hierarchy. The database column should be a nullable int.
The backend table will render children of an entry indented below the parent.
In the edit form you can select the parent in a select form element

No additional configuration

#### ReadOnly
A field that is not editable in the backend form.
It can be used for fields that are modified by other actions, e.g. a version information that will be increased automatically on save
(for this, use @link BackendTableRoute::processDataBeforeSaving())

Optional configuration:
- default (mixed): Initial value of this field when no value has been saved yet

#### Relation
Defines a field that references on another table, can be used e.g. for referencing a post to a user

Mandatory Configuration:
- linkedTable: tableName of the referenced table

Optional Configuration:
- primaryKey: column name of the primary key of the referenced table. Defaults to "id"
- displayTemplate: Template to format the referenced row in the backend. Column names can be referenced by enclosing them in curly brackets. E.g. "{first_name} {last_name}". Defaults to displaying the primaryKey.
- editDisplayTemplate: like display template, but used for the select field while editing. Defaults to displayTemplate
- where: SQL where clause, can be used restrict the available options. E.g. "'enabled' = 1"
- selectFields: to optimise performance, select the columns to be queried. Make sure all columns reference in the displayTemplate are included here. Defaults to ""
- allowEmptyValue: allows the field to be null. Defaults to true
  /

#### Select
Display a select/dropdown field where the exactly one option can be chosen

Required configuration fields:
- `options` (array<string> or map<string, string>) all available options. In the backend the key
  will be saved, in case of a json array this will be the index. Beware of this when modifying options
  later

see BitMap for multi-selection

#### SortOrder

Use this field if your table allows sorting. The database column should be integer.
The backend table will render drag handles. Make sure to set `showInTable` to true.
When also using `ParentId`, note that sorting only occurs by parent, not globally

Note that pagination will be disabled when a sortOrder field is present

No additional configuration


#### Special

A field that will not be saved in the database or shown in an edit form
Instead, during rendering, the `processSpecialField` method within your `Route` will be called
allowing for custom behaviour like e.g. showing dependencies

No additional configuration

#### TextArea

Displays an multiline text input field

Optional configuration:
- `default` (string): Initial value of this field when no value has been saved yet
- `rows` (int): number of lines initially displayed, default 3

#### WYSIWYG

Displays a WYSIWYG using TinyMCE (https://www.tiny.cloud/get-tiny/downloads/)
Requires the library to be downloaded into `js/tinymce`

Optional configuration fields:
- `rows` (int): number of lines initially displayed, default 5
- `preview` (map): options for the backend table preview with the following fields:
    - plainText (boolean): if true, the backend table preview will be without html tags
    - maxCharacters (int): maximum number of characters displayed in the preview