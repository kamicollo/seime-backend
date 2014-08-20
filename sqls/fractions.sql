INSERT INTO members (id, fraction)
SELECT A.members_id, A.fraction FROM votes A 
JOIN (SELECT members_id, MAX(id) as id FROM votes GROUP BY members_id) B
ON A.id = B.id AND A.members_id = B.members_id
ON DUPLICATE KEY UPDATE fraction = VALUES(fraction)
