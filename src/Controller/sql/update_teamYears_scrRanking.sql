UPDATE team_years ty1
    LEFT JOIN (
        SELECT q.*, (@rownum := @rownum + 1) AS rr
        FROM (
                 SELECT max(ty2.id) as id, sum(scr.points * scr.confirmed) AS pp
                    FROM team_years ty2
                             LEFT JOIN matches m ON ((m.refereeTeamSubst_id IS NOT NULL AND m.refereeTeamSubst_id=ty2.team_id) OR (m.refereeTeamSubst_id IS NULL AND m.refereeTeam_id=ty2.team_id))
                             LEFT JOIN matchevent_logs mel ON mel.match_id=m.id
                             LEFT JOIN scout_ratings scr ON scr.matchevent_log_id=mel.id
                             INNER JOIN `groups` g ON m.group_id = g.id

                 WHERE ty2.year_id=:year_id AND g.year_id=:year_id
                 GROUP BY ty2.id
                 ORDER BY pp DESC
             ) q
                 CROSS JOIN (SELECT @rownum := 0) ff
    ) p ON ty1.id = p.id

SET ty1.scrPoints = p.pp,
    ty1.scrRanking = p.rr
WHERE p.pp IS NOT NULL;
