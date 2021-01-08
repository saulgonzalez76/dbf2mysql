# dbf2mysql

Php service to convert and create tables from dbase files(dbf) into mysql database.

## Installation

Clone repo into your webserver directory, needs PHP 7 or above.

## ToDo
CSV files not yet implemented.<br>
TAR compressed files not yet implemented.<br>
ZIP files that contain txt or csv will not run, yet.

## Usage

Use your favorite browser and go to http://loclahost/dbf2mysql <br>
Fill the credentials for mysql server, must have a database created. <br>
Drag and drop files, admits zip, tar, csv and dbf extensions to convert them into mysql tables, 
each file will become a new table on the specified database.<br>
Wait for the files to be converted, dbf files in most cases have huge amount of data, so they could take a while to process depending on the server specs.



## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)