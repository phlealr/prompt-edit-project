-- Runs once, when the PostgreSQL volume is first created: the separate database the test suite uses (ADR 0003).
CREATE DATABASE prompt_edit_testing OWNER prompt_edit;
