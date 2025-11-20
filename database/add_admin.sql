USE food_hub;

INSERT INTO users (name, email, password, role) 
VALUES (
    'Admin',
    'admin@foodhub.com',
    '$2y$10$FKqEKHDVZZYCn7r8ZZ9zYuJXJ0.d3Iq0NuHrUXaWz/u.JCxAVKhHi', -- This is the hashed value for 'Admin@123'
    'admin'
);
