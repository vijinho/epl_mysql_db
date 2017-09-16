<?php

// Author: Vijay Mahrra <vijay@yoyo.org>
// Utility script for parsing csv files from
// http://www.football-data.co.uk/
// http://www.football-data.co.uk/notes.txt

// run with
// php goals.php  | mysql -uroot -proot football -f

# array to load results data from (CSV file to named table) and generate SQL for column names
$files = [
//    'E0-1993.csv' => 'EPL1993',
//    'E0-1994.csv' => 'EPL1994',
//    'E0-1995.csv' => 'EPL1995',
//    'E0-1996.csv' => 'EPL1996',
//    'E0-1997.csv' => 'EPL1997',
//    'E0-1998.csv' => 'EPL1998',
//    'E0-1999.csv' => 'EPL1999',
//    'E0-2000.csv' => 'EPL2000',
//    'E0-2001.csv' => 'EPL2001',
//    'E0-2002.csv' => 'EPL2002',
//    'E0-2003.csv' => 'EPL2003',
//    'E0-2004.csv' => 'EPL2004',
//    'E0-2005.csv' => 'EPL2005',
//    'E0-2006.csv' => 'EPL2006',
//    'E0-2007.csv' => 'EPL2007',
//    'E0-2008.csv' => 'EPL2008',
//    'E0-2009.csv' => 'EPL2009',
//    'E0-2010.csv' => 'EPL2010',
//    'E0-2011.csv' => 'EPL2011',
//    'E0-2012.csv' => 'EPL2012',
//    'E0-2013.csv' => 'EPL2013',
//    'E0-2014.csv' => 'EPL2014',
//    'E0-2015.csv' => 'EPL2015',
//    'E0-2016.csv' => 'EPL2016',
    'E0-2017.csv' => 'EPL2017',
];

// take in home team score, away team score
// return -1, 0, 1 for (home, draw, away)
function getResults($hgoals, $agoals) {
    if ($hgoals > $agoals) {
        $result = -1; // home win
    } else if ($hgoals < $agoals) {
        $result = 1; // away win
    }
    return (int) empty($result) ? 0 : $result; // empty = draw!
}

// read each file and generate sql to convert phpmyadmin mysql import of csv

foreach ($files as $datafile => $table) {
    //$table = 'EPL2017';
    //$datafile = 'E0-2017.csv';

    /*
    LOAD DATA LOCAL INFILE 'abc.csv' INTO TABLE abc
    FIELDS TERMINATED BY ','
    ENCLOSED BY '"'
    LINES TERMINATED BY '\r\n'
    IGNORE 1 LINES
    (col1, col2, col3, col4, col5...);
    */

    // read in results data file
    ini_set('auto_detect_line_endings',TRUE);
    $fh = fopen($datafile,'r');
    $cols = fgetcsv($fh);
    $results = [];

    $teams = [];
    while ($row = fgetcsv($fh)) {
        foreach ($row as $k => $v) {
            $data[$cols[$k]] = $v;
        }

        // get teams
        if (!array_key_exists($data['HomeTeam'], $teams)) {
            $teams[$data['HomeTeam']] = $data['HomeTeam'];
        }

        // home, win, draw results, set to RESULT=(-1|0|1)
        $data['HT_RESULT'] = getResults($data['HTHG'], $data['HTAG']);
        $data['FT_RESULT'] = getResults($data['FTHG'], $data['FTAG']);

        // count half-time results
        $htr = $data['HT_RESULT'];
        if ($htr == -1) {
            $hthomes++;
        } elseif ($htr == 1) {
            $htaways++;
        } else {
            $htdraws++;
        }

        // count full-time results
        $ftr = $data['FT_RESULT'];
        if ($ftr == -1) {
            $fthomes++;
        } elseif ($htr == 1) {
            $ftaways++;
        } else {
            $ftdraws++;
        }

        // add processed data to results
        $results[] = $data;
        unset($data);
    }
    ksort($teams);

    $played = count($results);
    //echo "Played $played matches.\n";
    //echo "Half-Time: (H, D, A) $hthomes, $htdraws, $htaways.\n";
    //echo "Full-Time: (H, D, A) $fthomes, $ftdraws, $ftaways.\n";

    //print_r($teams);
    //print_r($results);
    //print_r(count($results));

    // for mysql import with phpmyadmin, fields by default called 'COL 1...' so need to rename
    // ALTER TABLE `EPL2016` CHANGE `COL 1` `Div` VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
    foreach ($cols as $i => $name) {
        unset($cols[$i]);
        $field = 'COL ' . ($i + 1);
        $cols[$field] = $name;
    }

    //echo "DELETE FROM `$table` WHERE `Div` = 'Div';\n";
    $query = "ALTER TABLE `%s` CHANGE `%s` `%s` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;\n";
    foreach ($cols as $field => $name) {
        printf($query, $table, $field, $name);
    }
}


/* example of creating amalgamated table, this works straight-off:

-- ORIGINAL CREATE QUERIES:
-- CREATE TABLE EPL
--	   SELECT * FROM EPL2017;
-- INSERT INTO EPL SELECT * FROM EPL2015;
-- INSERT INTO EPL SELECT * FROM EPL2016;
-- INSERT INTO EPL SELECT * FROM EPL2017;

-- CUSTOM TABLE SQL FOR IMPORT
CREATE TABLE `EPL` (
  `Div` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `Date` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `HomeTeam` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `AwayTeam` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `FTHG` tinyint(255) DEFAULT NULL,
  `FTAG` tinyint(255) DEFAULT NULL,
  `FTR` tinyint(255) DEFAULT NULL,
  `HTHG` tinyint(255) DEFAULT NULL,
  `HTAG` tinyint(255) DEFAULT NULL,
  `HTR` tinyint(255) DEFAULT NULL,
  `Referee` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `HS` tinyint(255) DEFAULT NULL,
  `AS` tinyint(255) DEFAULT NULL,
  `HST` tinyint(255) DEFAULT NULL,
  `AST` tinyint(255) DEFAULT NULL,
  `HF` tinyint(255) DEFAULT NULL,
  `AF` tinyint(255) DEFAULT NULL,
  `HC` tinyint(255) DEFAULT NULL,
  `AC` tinyint(255) DEFAULT NULL,
  `HY` tinyint(255) DEFAULT NULL,
  `AY` tinyint(255) DEFAULT NULL,
  `HR` tinyint(255) DEFAULT NULL,
  `AR` tinyint(255) DEFAULT NULL,
  `B365H` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `B365D` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `B365A` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `BWH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `BWD` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `BWA` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `IWH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `IWD` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `IWA` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `LBH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `LBD` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `LBA` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `PSH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `PSD` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `PSA` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `WHH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `WHD` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `WHA` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `VCH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `VCD` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `VCA` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `Bb1X2` int(255) DEFAULT NULL,
  `BbMxH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `BbAvH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `BbMxD` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `BbAvD` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `BbMxA` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `BbAvA` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `BbOU` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `BbMx>2.5` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `BbAv>2.5` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `BbMx<2.5` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `BbAv<2.5` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `BbAH` int(255) DEFAULT NULL,
  `BbAHh` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `BbMxAHH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `BbAvAHH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `BbMxAHA` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `BbAvAHA` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `PSCH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `PSCD` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `PSCA` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `Attendance` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `HHW` smallint(255) DEFAULT NULL,
  `AHW` smallint(255) DEFAULT NULL,
  `HO` smallint(255) DEFAULT NULL,
  `AO` smallint(255) DEFAULT NULL,
  `HBP` smallint(255) DEFAULT NULL,
  `ABP` smallint(255) DEFAULT NULL,
  `GBH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `GBD` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `GBA` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `SBH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `SBO` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `SBA` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `SBD` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `GB>2.5` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `GB<2.5` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `B365>2.5` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `B365<2.5` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `GBAHH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `GBAH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `LBAHH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `LBAHA` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `LBAH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `B365AHH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `B365AHA` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `B365AH` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `SEASON` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `MatchDate` datetime DEFAULT NULL
) ENGINE=InnoDB;

    INSERT INTO EPL
    	(`Div`, `Date`, HomeTeam, AwayTeam, FTHG, FTAG, FTR)
    	SELECT
    		`Div`, `Date`, HomeTeam, AwayTeam, FTHG, FTAG, FTR
    	FROM EPL1993;
    ;

    INSERT INTO EPL
    	(`Div`, `Date`, HomeTeam, AwayTeam, FTHG, FTAG, FTR)
    	SELECT
    		`Div`, `Date`, HomeTeam, AwayTeam, FTHG, FTAG, FTR
    	FROM EPL1994;
    ;

    INSERT INTO EPL
    	(`Div`, `Date`, HomeTeam, AwayTeam, FTHG, FTAG, FTR, HTHG, HTAG, HTR)
    	SELECT
    		`Div`, `Date`, HomeTeam, AwayTeam, FTHG, FTAG, FTR, HTHG, HTAG, HTR
    	FROM EPL1995;
    ;

-- Repeat prev query for each year to 1999
-- NOTE: will now need to add columns missing from amalgamated EPL table
INSERT INTO EPL
    (`Div`, `Date`, HomeTeam, AwayTeam, FTHG, FTAG, FTR, HTHG, HTAG, HTR, Attendance, Referee,
    HS, `AS`, HST, AST, HHW, AHW, HC, AC, HF, AF, HO, AO, HY, AY, HR, AR, HBP, ABP,
    GBH, GBD, GBA,  IWH, IWD, IWA, LBH, LBD, LBA, SBH, SBD, SBA, WHH, WHD, WHA)
    SELECT
        `Div`, `Date`, HomeTeam, AwayTeam, FTHG, FTAG, FTR, HTHG, HTAG, HTR, Attendance, Referee,
    HS, `AS`, HST, AST, HHW, AHW, HC, AC, HF, AF, HO, AO, HY, AY, HR, AR, HBP, ABP,
    GBH, GBD, GBA,  IWH, IWD, IWA, LBH, LBD, LBA, SBH, SBD, SBA, WHH, WHD, WHA
    FROM EPL2000;
;
-- REPEAT EPL2001

INSERT INTO EPL
    (`Div`, `Date`, HomeTeam, AwayTeam, FTHG, FTAG, FTR, HTHG, HTAG, HTR, Referee,
    HS, `AS`, HST, AST, HC, AC, HF, AF, HY, AY, HR, AR,
    GBH, GBD, GBA,  IWH, IWD, IWA, LBH, LBD, LBA, SBH, SBD, SBA, WHH, WHD, WHA,
    `GB>2.5`, `GB<2.5`,
    `B365>2.5`, `B365<2.5`
    )
    SELECT
        `Div`, `Date`, HomeTeam, AwayTeam, FTHG, FTAG, FTR, HTHG, HTAG, HTR, Referee,
    HS, `AS`, HST, AST, HC, AC, HF, AF, HY, AY, HR, AR,
    GBH, GBD, GBA,  IWH, IWD, IWA, LBH, LBD, LBA, SBH, SBD, SBA, WHH, WHD, WHA,
    `GB>2.5`, `GB<2.5`,
    `B365>2.5`, `B365<2.5`

    FROM EPL2002;
;

INSERT INTO EPL
    (`Div`, `Date`, HomeTeam, AwayTeam, FTHG, FTAG, FTR, HTHG, HTAG, HTR, Referee,
    HS, `AS`, HST, AST, HC, AC, HF, AF, HY, AY, HR, AR,
    B365H, B365D, B365A,
    BWH, BWD, BWA,
    GBH, GBD, GBA,  IWH, IWD, IWA, LBH, LBD, LBA, SBH, SBD, SBA, WHH,
    WHD, WHA, VCH, VCD, VCA,
    Bb1X2, BbMxH, BbAvH, BbMxD, BbMxA, BbAvA, BbOU, `BbAv>2.5`, `BbMx<2.5`, `BbAv<2.5`,
    BbAH, BbAHh, BbMxAHH, BbAvAHH, BbMxAHA, BbAvAHA
    )
    SELECT
        `Div`, `Date`, HomeTeam, AwayTeam, FTHG, FTAG, FTR, HTHG, HTAG, HTR, Referee,
    HS, `AS`, HST, AST, HC, AC, HF, AF, HY, AY, HR, AR,
    B365H, B365D, B365A,
    BWH, BWD, BWA,
    GBH, GBD, GBA,  IWH, IWD, IWA, LBH, LBD, LBA, SBH, SBD, SBA, WHH,
    WHD, WHA, VCH, VCD, VCA,
    Bb1X2, BbMxH, BbAvH, BbMxD, BbMxA, BbAvA, BbOU, `BbAv>2.5`, `BbMx<2.5`, `BbAv<2.5`,
    BbAH, BbAHh, BbMxAHH, BbAvAHH, BbMxAHA, BbAvAHA

    FROM EPL2005;
;

-- Repeat 2006, 2007, 2008, 2009, 2010

INSERT INTO EPL
    (`Div`, `Date`, HomeTeam, AwayTeam, FTHG, FTAG, FTR, HTHG, HTAG, HTR, Referee,
    HS, `AS`, HST, AST, HC, AC, HF, AF, HY, AY, HR, AR,
    B365H, B365D, B365A,
    BWH, BWD, BWA,
    GBH, GBD, GBA,  IWH, IWD, IWA, LBH, LBD, LBA, WHH,
    WHD, WHA, VCH, VCD, VCA,
    Bb1X2, BbMxH, BbAvH, BbMxD, BbMxA, BbAvA, BbOU, `BbAv>2.5`, `BbMx<2.5`, `BbAv<2.5`,
    BbAH, BbAHh, BbMxAHH, BbAvAHH, BbMxAHA, BbAvAHA
    )
    SELECT
        `Div`, `Date`, HomeTeam, AwayTeam, FTHG, FTAG, FTR, HTHG, HTAG, HTR, Referee,
    HS, `AS`, HST, AST, HC, AC, HF, AF, HY, AY, HR, AR,
    B365H, B365D, B365A,
    BWH, BWD, BWA,
    GBH, GBD, GBA,  IWH, IWD, IWA, LBH, LBD, LBA, WHH,
    WHD, WHA, VCH, VCD, VCA,
    Bb1X2, BbMxH, BbAvH, BbMxD, BbMxA, BbAvA, BbOU, `BbAv>2.5`, `BbMx<2.5`, `BbAv<2.5`,
    BbAH, BbAHh, BbMxAHH, BbAvAHH, BbMxAHA, BbAvAHA

    FROM EPL2011;
;

INSERT INTO EPL
    (`Div`, `Date`, HomeTeam, AwayTeam, FTHG, FTAG, FTR, HTHG, HTAG, HTR, Referee,
    HS, `AS`, HST, AST, HC, AC, HF, AF, HY, AY, HR, AR,
    B365H, B365D, B365A,
    BWH, BWD, BWA,
    GBH, GBD, GBA,  IWH, IWD, IWA, LBH, LBD, LBA, WHH,
    WHD, WHA, VCH, VCD, VCA,
    Bb1X2, BbMxH, BbAvH, BbMxD, BbMxA, BbAvA, BbOU, `BbAv>2.5`, `BbMx<2.5`, `BbAv<2.5`,
    BbAH, BbAHh, BbMxAHH, BbAvAHH, BbMxAHA, BbAvAHA,
    PSCH, PSCD, PSCA
    )
    SELECT
        `Div`, `Date`, HomeTeam, AwayTeam, FTHG, FTAG, FTR, HTHG, HTAG, HTR, Referee,
    HS, `AS`, HST, AST, HC, AC, HF, AF, HY, AY, HR, AR,
    B365H, B365D, B365A,
    BWH, BWD, BWA,
    GBH, GBD, GBA,  IWH, IWD, IWA, LBH, LBD, LBA, WHH,
    WHD, WHA, VCH, VCD, VCA,
    Bb1X2, BbMxH, BbAvH, BbMxD, BbMxA, BbAvA, BbOU, `BbAv>2.5`, `BbMx<2.5`, `BbAv<2.5`,
    BbAH, BbAHh, BbMxAHH, BbAvAHH, BbMxAHA, BbAvAHA,
    PSCH, PSCD, PSCA
    FROM EPL2012;
;

INSERT INTO EPL
    (`Div`, `Date`, HomeTeam, AwayTeam, FTHG, FTAG, FTR, HTHG, HTAG, HTR, Referee,
    HS, `AS`, HST, AST, HC, AC, HF, AF, HY, AY, HR, AR,
    B365H, B365D, B365A,
    BWH, BWD, BWA,
    IWH, IWD, IWA, LBH, LBD, LBA, WHH,
    WHD, WHA, VCH, VCD, VCA,
    Bb1X2, BbMxH, BbAvH, BbMxD, BbMxA, BbAvA, BbOU, `BbAv>2.5`, `BbMx<2.5`, `BbAv<2.5`,
    BbAH, BbAHh, BbMxAHH, BbAvAHH, BbMxAHA, BbAvAHA,
    PSCH, PSCD, PSCA
    )
    SELECT
        `Div`, `Date`, HomeTeam, AwayTeam, FTHG, FTAG, FTR, HTHG, HTAG, HTR, Referee,
    HS, `AS`, HST, AST, HC, AC, HF, AF, HY, AY, HR, AR,
    B365H, B365D, B365A,
    BWH, BWD, BWA,
    IWH, IWD, IWA, LBH, LBD, LBA, WHH,
    WHD, WHA, VCH, VCD, VCA,
    Bb1X2, BbMxH, BbAvH, BbMxD, BbMxA, BbAvA, BbOU, `BbAv>2.5`, `BbMx<2.5`, `BbAv<2.5`,
    BbAH, BbAHh, BbMxAHH, BbAvAHH, BbMxAHA, BbAvAHA,
    PSCH, PSCD, PSCA
    FROM EPL2013;
;

-- Repeat EPL2014, EPL2015, EPL2016, EPL2017

UPDATE EPL SET SEASON = '1993-1994' WHERE MatchDate BETWEEN '1993-07-01' AND '1994-08-01';
UPDATE EPL SET SEASON = '1994-1995' WHERE MatchDate BETWEEN '1994-07-01' AND '1995-08-01';
UPDATE EPL SET SEASON = '1995-1996' WHERE MatchDate BETWEEN '1995-07-01' AND '1996-08-01';
UPDATE EPL SET SEASON = '1996-1997' WHERE MatchDate BETWEEN '1996-07-01' AND '1997-08-01';
UPDATE EPL SET SEASON = '1997-1998' WHERE MatchDate BETWEEN '1997-07-01' AND '1998-08-01';
UPDATE EPL SET SEASON = '1998-1999' WHERE MatchDate BETWEEN '1998-07-01' AND '1999-08-01';
UPDATE EPL SET SEASON = '1999-2000' WHERE MatchDate BETWEEN '1999-07-01' AND '2000-08-01';
UPDATE EPL SET SEASON = '2001-2002' WHERE MatchDate BETWEEN '2001-07-01' AND '2002-08-01';
UPDATE EPL SET SEASON = '2002-2003' WHERE MatchDate BETWEEN '2002-07-01' AND '2003-08-01';
UPDATE EPL SET SEASON = '2003-2004' WHERE MatchDate BETWEEN '2003-07-01' AND '2004-08-01';
UPDATE EPL SET SEASON = '2004-2005' WHERE MatchDate BETWEEN '2004-07-01' AND '2005-08-01';
UPDATE EPL SET SEASON = '2005-2006' WHERE MatchDate BETWEEN '2005-07-01' AND '2006-08-01';
UPDATE EPL SET SEASON = '2006-2007' WHERE MatchDate BETWEEN '2006-07-01' AND '2007-08-01';
UPDATE EPL SET SEASON = '2007-2008' WHERE MatchDate BETWEEN '2007-07-01' AND '2008-08-01';
UPDATE EPL SET SEASON = '2008-2009' WHERE MatchDate BETWEEN '2008-07-01' AND '2009-08-01';
UPDATE EPL SET SEASON = '2009-2010' WHERE MatchDate BETWEEN '2009-07-01' AND '2010-08-01';
UPDATE EPL SET SEASON = '2010-2011' WHERE MatchDate BETWEEN '2010-07-01' AND '2011-08-01';
UPDATE EPL SET SEASON = '2011-2011' WHERE MatchDate BETWEEN '2011-07-01' AND '2012-08-01';
UPDATE EPL SET SEASON = '2012-2013' WHERE MatchDate BETWEEN '2012-07-01' AND '2013-08-01';
UPDATE EPL SET SEASON = '2013-2014' WHERE MatchDate BETWEEN '2013-07-01' AND '2014-08-01';
UPDATE EPL SET SEASON = '2014-2015' WHERE MatchDate BETWEEN '2014-07-01' AND '2015-08-01';
UPDATE EPL SET SEASON = '2015-2016' WHERE MatchDate BETWEEN '2015-07-01' AND '2016-08-01';
UPDATE EPL SET SEASON = '2016-2017' WHERE MatchDate BETWEEN '2016-07-01' AND '2017-08-01';

UPDATE EPL SET MatchDate = STR_TO_DATE(`Date`,'%d/%m/%y');
UPDATE EPL SET SEASON = '2016-2017' WHERE MatchDate BETWEEN '2016-07-01' AND '2017-08-01';

*/
