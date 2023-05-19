CREATE TABLE issues (
  id INT AUTO_INCREMENT PRIMARY KEY,
  k VARCHAR(255) NOT NULL UNIQUE,
  title VARCHAR(255)
);

CREATE TABLE periods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  k VARCHAR(255) NOT NULL,
  assignee VARCHAR(20) NOT NULL,
  started_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
  ended_at DATETIME,
  finished char(1) NOT NULL DEFAULT 'n',
  INDEX(k)
)