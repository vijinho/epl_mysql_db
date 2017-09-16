-- Top 10 teams with most home wins
SELECT HomeTeam, COUNT(*) AS wins
FROM EPL
WHERE FTHG > FTAG
GROUP BY HomeTeam
ORDER BY COUNT(*) DESC
LIMIT 10;
