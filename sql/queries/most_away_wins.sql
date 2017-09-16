-- Top 10 teams with most away wins
SELECT AwayTeam, COUNT(*) AS wins
FROM EPL
WHERE FTHG < FTAG
GROUP BY AwayTeam
ORDER BY COUNT(*) DESC
LIMIT 10;
