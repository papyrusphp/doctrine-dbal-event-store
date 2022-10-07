CREATE TABLE `event_store`
(
    `id`                varchar(36) COLLATE utf8mb4_unicode_ci  NOT NULL,
    `aggregate_root_id` varchar(36) COLLATE utf8mb4_unicode_ci  NOT NULL,
    `event_name`        varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `payload`           json                                    NOT NULL,
    `playhead`          int                                     NOT NULL,
    `metadata`          json                                    NOT NULL,
    `applied_at`        timestamp(6)                            NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `aggregate_root_id` (`aggregate_root_id`,`playhead`),
    KEY                 `event_name` (`event_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
