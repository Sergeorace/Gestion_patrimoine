-- =============================================================================
-- Initialisation PostgreSQL - une base logique par micro-service
-- Execute au premier demarrage du conteneur postgres.
-- =============================================================================

-- Role applicatif unique (les services partagent l'instance, pas les bases)
-- Le mot de passe est celui de POSTGRES_USER defini via .env.

CREATE DATABASE auth_db;
CREATE DATABASE organisation_db;
CREATE DATABASE asset_db;
CREATE DATABASE movement_db;
CREATE DATABASE inventory_db;
CREATE DATABASE maintenance_db;
CREATE DATABASE valuation_db;
CREATE DATABASE document_db;
CREATE DATABASE notification_db;
CREATE DATABASE reporting_db;

-- Droits : le role proprietaire (POSTGRES_USER) a tous les droits sur ses bases.
-- Pour un cloisonnement plus strict en production, creer un role par service :
--   CREATE ROLE auth_svc LOGIN PASSWORD '...';
--   GRANT ALL ON DATABASE auth_db TO auth_svc;
