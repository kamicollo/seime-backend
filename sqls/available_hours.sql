INSERT INTO participation_data (members_id, sittings_id, hours_available)
					SELECT members_id, sittings_id, SUM(TIME_TO_SEC(TIMEDIFF(subquestions.end_time, subquestions.start_time))) / 3600 from subquestions_participation 
					JOIN subquestions on subquestions.id = subquestions_id 
					JOIN questions on questions_id = questions.id
					JOIN sittings on sittings_id = sittings.id						
					GROUP BY members_id, sittings_id			
				ON DUPLICATE KEY UPDATE participation_data.hours_available = VALUES(hours_available)
