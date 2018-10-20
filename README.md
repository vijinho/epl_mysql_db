# English Premier League Results Database (MySQL/sqlite)

> I heartily welcome patches to update this data or any football-related donations if you appreciate it, e.g. a postcard from your club shop for example -- vijay@yoyo.org

## Introduction

This is a raw set of imported data from http://www.football-data.co.uk with each Premier League CSV results data file at http://www.football-data.co.uk/englandm.php raw SQL imported using PhpMyAdmin into a separate table, numbered EPL1993-EPL2018 (will need to be updated regularly until the season is over).

A table with raw data of all of the combined results from each season 'EPL.SQL' has been created with an ad-hoc script 'csv/goals.php' which was used to process each CSV file to generate some SQL.  This script can be modified to do many other things, I'll leave that to you!  It basically is used after loading one of the CSV files into phpmyadmin and then renaming the newly created raw sql data table from 'TABLE 99' to EPL2017, and then outputs the SQL to rename the columns from COL1, COL2 etc to their actual column names.

Please refer to `notes.txt` for information about what the data means as well as the original source material for the data at http://www.football-data.co.uk/englandm.php

### Why?

I was not satisfied with my inability to analyse results further than on existing statistics websites like the excellent http://www.soccerstats.com and wanted to be able to run queries to generate knowledge and rules-of-thumb to use when betting.

## Instructions

### Just browsing the data with SQL

Download an sqlite tool like [sqlitebrowser.org](http://sqlitebrowser.org/) application or [phpliteadmin.org](phpliteadmin.org/) and open the *.sqlite* file in the *sql* data folder.

### Single database table of EPL results

Import the MySQL dump file `EPL_Seasons_1993-2017_RAW_Table.sql` for the
combined table of all historical EPL results up to the start of 2018-2019
season.  This table has all the columns of all of the CSV files and the RAW data and as well as an extra couple of columns 'SEASON' for the SEASON/YEAR (i.e. 1994-1995) and also the Date column has been converted to a MySQL compatible DATE format with.  (See the SQL for this at the end of `goals.php`)

### Multiple tables for each EPL season's results
Import the MySQL dump file `EPL_Seasons_1993-2017_RAW_Tables.sql` for the
individual tables of all historical EPL results up to start of season 2018.  This table has all the columns of all of the CSV files and the RAW data and as well as an extra couple of columns 'SEASON' for the SEASON/YEAR (i.e. 1994-1995) and also the Date column has been converted to a MySQL compatible DATE format with.  (See the SQL for this at the end of `goals.php`)

## Updating (for 2018-19 & future seasons)

1. Get the latest CSV file of results from http://www.football-data.co.uk/englandm.php
2. Put the file in the folder CSV, overwriting `E0-2018.csv`
3. In *phpMyAdmin* use the import feature to import the CSV file to the database
4. On the command-line, run `php goals.php` to generate the SQL to rename the columns in the new DB table
5. Refer to the SQL at the end of `goals.php` on how to update this newly created table
6. Delete current 2016-2017 season results from the database table `EPL` with `DELETE FROM EPL WHERE matchdate > '2018-08-01';`

## Updating the sqlite database/converting between databases

Go to [rebasedata.com/convert-mysql-to-sqlite-online](https://www.rebasedata.com/convert-mysql-to-sqlite-online) for instructions on how to easily convert the data between various DBMSs.  This is how it was done originally:

```
curl -F files[]=@EPL_Seasons_1993-2017_RAW_Tables.sql 'https://www.rebasedata.com/api/v1/convert?outputFormat=sqlite&errorResponse=zip' -o EPL_Seasons_1993-2017_RAW_Tables.sqlite.zip

unzip EPL_Seasons_1993-2017_RAW_Tables.sqlite.zip

mv data.sqlite EPL_Seasons_1993-2017_RAW_Tables.sqlite
```

7. Insert the latest results from the newly created table to the master combined table `EPL` of all historical results

## Example Queries

Refer to `sql/queries/` and feel free to send any other queries with a github pull request.

## Simple Example

e.g. Top-ten teams with most home wins in EPL history, in descending order (up to end of 2017-2018 season) (works on mysql and sqlite):

```
SELECT HomeTeam, COUNT(*) AS wins
FROM EPL
WHERE FTHG > FTAG
GROUP BY HomeTeam
ORDER BY COUNT(*) DESC
LIMIT 10;

"Man United"	"349"
"Arsenal"	"315"
"Chelsea"	"304"
"Liverpool"	"290"
"Tottenham"	"253"
"Everton"	"227"
"Newcastle"	"225"
"Man City"	"212"
"West Ham"	"182"
"Aston Villa"	"175"
```

## Complicated Example

### Complete record between two teams matches

Show all results between West Ham (Home) versus Tottenham (Away), Full-Time Result & Half-Time scores, by latest season descending (mysql-specific example).

```
SELECT SEASON, `Date`,
	LEFT(MatchDate,11) AS `Date`,
	HomeTeam,
	AwayTeam,
	CONCAT(FTHG, '-', FTAG) AS Result,
	CONCAT(HTHG, '-', HTAG) AS HalfTime
FROM EPL
WHERE HomeTeam = 'West Ham'
AND AwayTeam = 'Tottenham'
ORDER BY `DATE` DESC;

```
*Note:* The MatchDate and SEASON fields are incorrect on one of the results because we are working with RAW data from the import and the database isn't fully tidied-up and normalised yet.  To be 100% certain of no inconsistencies, make sure you view the original imported columns in your output results!

--
