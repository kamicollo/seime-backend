INSERT INTO participation_data
	(members_id, sittings_id, official_presence)
	SELECT members_id, sittings_id, presence as official_presence
		FROM sitting_participation
		JOIN sittings on sittings_id = sittings.id			
		ON DUPLICATE KEY UPDATE participation_data.id = participation_data.id
