ALTER TABLE posts
    ADD COLUMN visibility ENUM('public', 'on_request') NOT NULL DEFAULT 'public' AFTER content,
    ADD COLUMN access_token VARCHAR(64) NULL AFTER visibility,
    ADD KEY idx_posts_visibility (visibility);

UPDATE posts SET visibility = 'public' WHERE visibility IS NULL;
