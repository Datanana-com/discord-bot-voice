ALTER TABLE hoyxen_reports
    ADD COLUMN discord_message_id int(20) AFTER time,
    ADD COLUMN discord_claimed_by_user_id int(20) AFTER discord_message_id,
    ADD COLUMN discord_claimed_at int(12);

ALTER TABLE `hoyxen_reports`
	CHANGE COLUMN `discord_message_id` `discord_message_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `time`,
	CHANGE COLUMN `discord_claimed_by_user_id` `discord_claimed_by_user_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `discord_message_id`,
	CHANGE COLUMN `discord_claimed_at` `discord_claimed_at` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `discord_claimed_by_user_id`;