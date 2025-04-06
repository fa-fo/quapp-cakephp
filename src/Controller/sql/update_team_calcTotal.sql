UPDATE teams t1
    LEFT JOIN (
        SELECT q.*, (@rownum := @rownum + 1) AS rr
        FROM (
                 SELECT max(t2.id) as id, count(ty.id) AS cc, sum(ty.endRanking = 1) AS ww, sum(rp.points) AS pp
                 FROM teams t2
                          LEFT JOIN team_years ty ON (t2.id = ty.team_id AND ty.canceled = 0 AND ty.endRanking > 0 AND
                                                      ty.endRanking IS NOT NULL)
                          LEFT JOIN rankingpoints rp ON ty.endRanking = rp.endRanking
                 GROUP BY ty.team_id
                 ORDER BY pp DESC, cc DESC, ww DESC
             ) q
                 CROSS JOIN (SELECT @rownum := 0) ff
    ) p ON t1.id = p.id

SET t1.calcTotalYears         = p.cc,
    t1.calcTotalRankingPoints = p.pp,
    t1.calcTotalPointsPerYear = FLOOR(100 * (p.pp / p.cc)) / 100,
    t1.calcTotalRanking       = p.rr,
    t1.calcTotalChampionships = p.ww
WHERE p.cc IS NOT NULL;
