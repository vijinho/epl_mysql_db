# English Premier League Results Database (MySQL)

## Introduction

This is a raw set of imported data from http://www.football-data.co.uk with each Premier League CSV results data file at http://www.football-data.co.uk/englandm.php raw SQL imported using PhpMyAdmin into a separate table, numbered EPL1993-EPL2017 (will need to be updated regularly until the season is over).

A table with raw data of all of the combined results from each season 'EPL.SQL' has been created with an ad-hoc script 'csv/goals.php' which was used to process each CSV file to generate some SQL.  This script can be modified to do many other things, I'll leave that to you!  It basically is used after loading one of the CSV files into phpmyadmin and then renaming the newly created raw sql data table from 'TABLE 99' to EPL2017, and then outputs the SQL to rename the columns from COL1, COL2 etc to their actual column names.

Please refer to `notes.txt` for information about what the data means as well as the original source material for the data at http://www.football-data.co.uk/englandm.php

### Why?

I was not satisfied with my inability to analyse results further than on existing statistics websites like the excellent http://www.soccerstats.com and wanted to be able to run queries to generate knowledge and rules-of-thumb to use when betting.

## Instructions

### Single database table of EPL results

Import the MySQL dump file `EPL_Seasons_1993-2017_RAW_Table.mysql` for the combined table of all historical EPL results up to 15-Sep-2017.  This table has all the columns of all of the CSV files and the RAW data and as well as an extra couple of columns 'SEASON' for the SEASON/YEAR (i.e. 1994-1995) and also the Date column has been converted to a MySQL compatible DATE format with.  (See the SQL for this at the end of `goals.php`)

### Multiple tables for each EPL season's results
Import the MySQL dump file `EPL_Seasons_1993-2016_RAW_Tables.mysql` for the combined table of all historical EPL results up to 15-Sep-2017.  This table has all the columns of all of the CSV files and the RAW data and as well as an extra couple of columns 'SEASON' for the SEASON/YEAR (i.e. 1994-1995) and also the Date column has been converted to a MySQL compatible DATE format with.  (See the SQL for this at the end of `goals.php`)

## Updating (for 2016-2017 & future seasons)

1. Get the latest CSV file of results from http://www.football-data.co.uk/englandm.php
2. Put the file in the folder CSV, overwriting `E0-2017.csv`
3. In *phpMyAdmin* use the import feature to import the CSV file to the database
4. On the command-line, run `php goals.php` to generate the SQL to rename the columns in the new DB table
5. Refer to the SQL at the end of `goals.php` on how to update this newly created table
6. Delete current 2016-2017 season results from the database table `EPL`
7. Insert the latest results from the newly created table to the master combined table `EPL` of all historical results


## Example Queries

Refer to `sql/queries/` and feel free to send any other queries with a github pull request.

## Simple Example

e.g. Top-ten teams with most home wins in EPL history, in descending order:

```
SELECT HomeTeam, COUNT(*) AS wins
FROM EPL
WHERE FTHG > FTAG
GROUP BY HomeTeam
ORDER BY COUNT(*) DESC
LIMIT 10;

Man United	312
Arsenal	274
Chelsea	268
Liverpool	258
Tottenham	222
Newcastle	200
Everton	198
Man City	184
West Ham	176
Aston Villa	158
```

## Complicated Example

### Complete record between two teams matches

Show all results between West Ham (Home) versus Tottenham (Away), Full-Time Result & Half-Time scores, by latest season descending.

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
