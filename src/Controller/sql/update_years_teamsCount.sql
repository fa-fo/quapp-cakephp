UPDATE years y1
    LEFT JOIN (
        SELECT y2.id, count(ty.id) as cc
        FROM years y2
                 LEFT JOIN team_years ty ON (y2.id = ty.year_id AND ty.canceled = 0)
        GROUP BY ty.year_id
    ) p ON y1.id = p.id

SET y1.teamsCount=p.cc

WHERE 1;
