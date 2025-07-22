UPDATE teams t1
    LEFT JOIN (SELECT q.*, (@rownum := @rownum + 1) AS rr
               FROM (SELECT t2.id,
                            ((COALESCE(rp1.points, 0) * 1.5
                                + COALESCE(rp2.points, 0) * 1.0
                                + COALESCE(rp3.points, 0) * .6
                                + COALESCE(rp4.points, 0) * .3
                                ) / ( if(rp1.points is not null, 1.5, 0)
                                    + if(rp2.points is not null, 1, 0)
                                    + if(rp3.points is not null, .6, 0)
                                    + if(rp4.points is not null, .3, 0)
                                    + if(rp1.points is null AND rp2.points is null AND rp3.points is null AND rp4.points is null, 1, 0)
                                 )
                                ) AS pp
                     FROM teams t2
                              LEFT JOIN team_years ty1 ON (ty1.year_id = :year_id && t2.id = ty1.team_id)
                              LEFT JOIN rankingpoints rp1 ON ty1.endRanking = rp1.endRanking
                              LEFT JOIN team_years ty2 ON (ty2.year_id = (:year_id - 1) && t2.id = ty2.team_id)
                              LEFT JOIN rankingpoints rp2 ON ty2.endRanking = rp2.endRanking
                              LEFT JOIN team_years ty3 ON (ty3.year_id = (:year_id - 2) && t2.id = ty3.team_id)
                              LEFT JOIN rankingpoints rp3 ON ty3.endRanking = rp3.endRanking
                              LEFT JOIN team_years ty4 ON (ty4.year_id = (:year_id - 3) && t2.id = ty4.team_id)
                              LEFT JOIN rankingpoints rp4 ON ty4.endRanking = rp4.endRanking
                     ORDER BY pp DESC) q
                        CROSS JOIN (SELECT @rownum := 0) ff) p ON t1.id = p.id

SET t1.calcPowerRankingPoints = p.pp
WHERE 1;
