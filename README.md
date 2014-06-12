OSFI Check
==========
Performs a name / entity check against the list provided by the Office of the Superintendent of Financial Institutions (OSFI). Whenever a person in the OSFI list has multiple names(eg: Saint John Silas the Second), all permutations of their names are inserted to the index for easier searching. A name with 5 parts will result in up to 325 inserts, each with different combinations of the name in metaphone as a PartitionKey. Searching by partition is immediate (kv).


Webjob
------
A [webjob](http://azure.microsoft.com/en-us/documentation/articles/web-sites-create-web-jobs/) is implemented in the `run.php` file, which downloads the latest data from the OSFI endpoints and inserts it into the the database. You can simply call `php run.php` locally to populate the database or alternatively you can deploy to azure by doing the following:

1. Set the `CUSTOMCONNSTR_OSFI_CONN_STRING` environment variable to your own credentials
2. Run `composer update`
3. Zip the inside of the project directory and upload to azure via the box in management console


REST Api
--------
A self contained REST API (using the slim microframework) is located in the `index.php` file. To test locally, run `php -S localhost:8080` in the project directory.