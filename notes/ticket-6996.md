# Database changes

    CREATE TABLE dialer_teams (
    id BIGINT UNSIGNED auto_increment NOT NULL,
    name varchar(255) NULL,
    is_archived TINYINT UNSIGNED DEFAULT 0 NOT NULL,
    manager_agent_id BIGINT UNSIGNED NULL,
    CONSTRAINT dialer_teams_PK PRIMARY KEY (id),
    CONSTRAINT dialer_teams_manager_FK FOREIGN KEY (manager_agent_id) REFERENCES dialer_agents(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT dialer_teams_lead_FK FOREIGN KEY (lead_agent_id) REFERENCES dialer_agents(id) ON DELETE RESTRICT ON UPDATE RESTRICT
    )
    ENGINE=InnoDB
    DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_0900_ai_ci;

    CREATE TABLE `dialer_agent_writeups` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `agent_id` bigint unsigned NOT NULL,
    `reporter_user_id` smallint unsigned NOT NULL,
    `date` date DEFAULT NULL,
    `reason_id` bigint unsigned DEFAULT NULL,
    `notes` text,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `dialer_agent_writeups_agents_FK` (`agent_id`),
    KEY `dialer_agent_writeups_users_FK` (`reporter_user_id`),
    KEY `dialer_agent_writeups_reasons_FK` (`reason_id`),
    CONSTRAINT `dialer_agent_writeups_agents_FK` FOREIGN KEY (`agent_id`) REFERENCES `dialer_agents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `dialer_agent_writeups_reasons_FK` FOREIGN KEY (`reason_id`) REFERENCES `dialer_agent_writeup_reasons` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `dialer_agent_writeups_users_FK` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`idUser`) ON DELETE RESTRICT ON UPDATE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

    ALTER TABLE dialer_agents ADD CONSTRAINT dialer_agents_team_FK FOREIGN KEY (team_id) REFERENCES dialer_teams(id) ON DELETE RESTRICT ON UPDATE RESTRICT;

    ALTER TABLE dialer_agent_performances ADD successful_transfers_bill_time MEDIUMINT UNSIGNED NULL;
    ALTER TABLE dialer_agent_performances ADD under_5_min SMALLINT UNSIGNED NULL;

    CREATE TABLE `dialer_team_leads` (
    `team_id` bigint unsigned NOT NULL,
    `agent_id` bigint unsigned NOT NULL,
    KEY `dialer_team_leads_FK` (`team_id`),
    KEY `dialer_team_leads_FK_1` (`agent_id`),
    CONSTRAINT `dialer_team_leads_FK` FOREIGN KEY (`team_id`) REFERENCES `dialer_teams` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `dialer_team_leads_FK_1` FOREIGN KEY (`agent_id`) REFERENCES `dialer_agents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

# Deployment instructions

# Potential breaking changes

# TODO

# Development notes

Testing with 2Anthonett 2Arnold
