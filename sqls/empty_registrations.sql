INSERT INTO votes (id, vote)
					SELECT votes.id, "disappeared" as vote FROM votes 
						JOIN voting_registration ON votes.actions_id = voting_registration.voting_id
						JOIN registrations ON voting_registration.registration_id = registrations.actions_id AND votes.members_id = registrations.members_id
					WHERE votes.vote = "not presen" AND registrations.presence = 1
				ON DUPLICATE KEY UPDATE votes.vote = VALUES(vote)
