INSERT INTO roles (code, name) VALUES
('admin', 'Admin'),
('bendahara', 'Bendahara'),
('pimpinan', 'Pimpinan');

INSERT INTO users (role_id, full_name, username, password_hash, is_active)
SELECT id, 'Administrator BUMDes', 'admin', '$2y$12$KVDZYCd5f2p17O1YK4ne2./D12t0Ai5fUtUDkv2yX8n8B5.SrpzEK', 1 FROM roles WHERE code='admin';
INSERT INTO users (role_id, full_name, username, password_hash, is_active)
SELECT id, 'Bendahara BUMDes', 'bendahara', '$2y$12$KVDZYCd5f2p17O1YK4ne2./D12t0Ai5fUtUDkv2yX8n8B5.SrpzEK', 1 FROM roles WHERE code='bendahara';
INSERT INTO users (role_id, full_name, username, password_hash, is_active)
SELECT id, 'Pimpinan BUMDes', 'pimpinan', '$2y$12$KVDZYCd5f2p17O1YK4ne2./D12t0Ai5fUtUDkv2yX8n8B5.SrpzEK', 1 FROM roles WHERE code='pimpinan';
