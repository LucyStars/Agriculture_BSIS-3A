-- =====================================================
-- agriculture_db: CREATE and INSERT statements
-- Converted from install.php and updated to the 3NF design (lookup tables)
-- Safe to re-run: the DROP below rebuilds every table (all data is deleted each run).
-- =====================================================

DROP TABLE IF EXISTS harvest, planting, farm, crop, farmer, users,
    user_role, farm_soil_type, farm_irrigation_type, crop_category,
    crop_recommended_season, planting_status, harvest_quality_grade, harvest_remark
CASCADE;

-- TRUNCATE TABLE harvest, planting, farm, crop, farmer,
--     user_role, farm_soil_type, farm_irrigation_type, crop_category,
--     crop_recommended_season, planting_status, harvest_quality_grade, harvest_remark
-- RESTART IDENTITY CASCADE;

-- =====================================================
-- CREATE LOOKUP TABLES
-- =====================================================

CREATE TABLE IF NOT EXISTS user_role (
    role_id SERIAL PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS users (
    user_id SERIAL PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    role_id INT NOT NULL,
    FOREIGN KEY (role_id) REFERENCES user_role(role_id) 
);

CREATE TABLE IF NOT EXISTS farm_soil_type (
    soil_type_id SERIAL PRIMARY KEY,
    soil_type_description VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS farm_irrigation_type (
    irrigation_type_id SERIAL PRIMARY KEY,
    irrigation_type_description VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS crop_category (
    category_id SERIAL PRIMARY KEY,
    category_description VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS crop_recommended_season (
    recommended_season_id SERIAL PRIMARY KEY,
    recommended_season_description VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS planting_status (
    status_id SERIAL PRIMARY KEY,
    status_description VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS harvest_quality_grade (
    quality_grade_id SERIAL PRIMARY KEY,
    quality_grade_description VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS harvest_remark (
    remarks_id SERIAL PRIMARY KEY,
    remarks_description VARCHAR(255) NOT NULL
);

-- =====================================================
-- LOOKUP DATA
-- =====================================================

INSERT INTO user_role (role_name)
VALUES
    ('Administrator'),
    ('Staff');

INSERT INTO farm_soil_type (soil_type_description)
VALUES
    ('Loam'),
    ('Clay'),
    ('Clay Loam'),
    ('Sandy Loam');

INSERT INTO farm_irrigation_type (irrigation_type_description)
VALUES
    ('Rainfed'),
    ('Irrigated'),
    ('Drip Irrigation');

INSERT INTO crop_category (category_description)
VALUES
    ('Cereal'),
    ('Vegetable'),
    ('Fruit'),
    ('Legume'),
    ('Root Crop');

INSERT INTO crop_recommended_season (recommended_season_description)
VALUES
    ('Wet Season'),
    ('Dry Season'),
    ('Year Round');

INSERT INTO planting_status (status_description)
VALUES
    ('Growing'),
    ('Harvested');

INSERT INTO harvest_quality_grade (quality_grade_description)
VALUES
    ('Grade A'),
    ('Grade B'),
    ('Grade C');

INSERT INTO harvest_remark (remarks_description)
VALUES
    ('Good Harvest'),
    ('Acceptable Quality'),
    ('Low Yield'),
    ('Poor Quality'),
    ('Damaged Harvest');

-- =====================================================
-- CREATE MAIN TABLES
-- =====================================================

CREATE TABLE IF NOT EXISTS farmer (
    farmer_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    contact_number VARCHAR(20),
    address VARCHAR(150),
    date_registered DATE DEFAULT CURRENT_DATE
);

CREATE TABLE IF NOT EXISTS farm (
    farm_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    farmer_id INT NOT NULL,
    farm_name VARCHAR(100) NOT NULL,
    location VARCHAR(150) NOT NULL,
    area_hectares NUMERIC(8,2)
        CHECK (area_hectares > 0),
    soil_type_id INT NOT NULL,
    irrigation_type_id INT NOT NULL,

    CONSTRAINT fk_farm_farmer
        FOREIGN KEY (farmer_id)
        REFERENCES farmer(farmer_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_farm_soil_type
        FOREIGN KEY (soil_type_id)
        REFERENCES farm_soil_type(soil_type_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_farm_irrigation_type
        FOREIGN KEY (irrigation_type_id)
        REFERENCES farm_irrigation_type(irrigation_type_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS crop (
    crop_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    crop_name VARCHAR(80) NOT NULL,
    variety VARCHAR(80),
    category_id INT NOT NULL,
    growing_period_days INT
        CHECK (growing_period_days > 0),
    recommended_season_id INT NOT NULL,
    CONSTRAINT fk_crop_category
		FOREIGN KEY (category_id)
        REFERENCES crop_category(category_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
        
    CONSTRAINT fk_crop_recommended_season
		FOREIGN KEY (recommended_season_id)
        REFERENCES crop_recommended_season(recommended_season_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS planting (
    planting_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    farm_id INT NOT NULL,
    crop_id INT NOT NULL,
    planting_date DATE NOT NULL,
    area_planted NUMERIC(8,2)
        CHECK (area_planted > 0),
    seed_quantity NUMERIC(8,2)
        CHECK (seed_quantity >= 0),
    expected_harvest_date DATE,
    status_id INT NOT NULL,
    CONSTRAINT fk_planting_farm
        FOREIGN KEY (farm_id)
        REFERENCES farm(farm_id)
        ON DELETE CASCADE,
        
    CONSTRAINT fk_planting_crop
        FOREIGN KEY (crop_id)
        REFERENCES crop(crop_id),
        
	CONSTRAINT fk_planting_status
		FOREIGN KEY (status_id)
        REFERENCES planting_status(status_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
        
    CONSTRAINT chk_harvest_date
        CHECK (
            expected_harvest_date IS NULL
            OR expected_harvest_date >= planting_date
        )
);

CREATE TABLE IF NOT EXISTS harvest (
    harvest_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    planting_id INT NOT NULL,
    harvest_date DATE NOT NULL,
    quantity_kg NUMERIC(10,2)
        CHECK (quantity_kg >= 0),
    selling_price_per_kg NUMERIC(10,2)
        CHECK (selling_price_per_kg >= 0),
    quality_grade_id INT NOT NULL,
    remarks_id INT NOT NULL,
    CONSTRAINT fk_harvest_planting
        FOREIGN KEY (planting_id)
        REFERENCES planting(planting_id)
        ON DELETE CASCADE,
        
	CONSTRAINT fk_harvest_quality_grade
		FOREIGN KEY (quality_grade_id)
        REFERENCES harvest_quality_grade(quality_grade_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
        
	CONSTRAINT fk_harvest_remark
		FOREIGN KEY (remarks_id)
        REFERENCES harvest_remark(remarks_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- =====================================================
-- 15 FARMERS
-- =====================================================

INSERT INTO farmer (first_name, last_name, contact_number, address)
VALUES
    ('Juan', 'Dela Cruz', '09171234567', 'Cebu City'),
    ('Maria', 'Santos', '09181234567', 'Carcar City, Cebu'),
    ('Pedro', 'Reyes', '09191234567', 'Danao City, Cebu'),
    ('Ana', 'Garcia', '09201234567', 'Argao, Cebu'),
    ('Jose', 'Mendoza', '09211234567', 'Bogo City, Cebu'),
    ('Ramon', 'Flores', '09221234567', 'Toledo City, Cebu'),
    ('Elena', 'Cruz', '09231234567', 'Naga City, Cebu'),
    ('Antonio', 'Lim', '09241234567', 'Minglanilla, Cebu'),
    ('Rosa', 'Fernandez', '09251234567', 'Liloan, Cebu'),
    ('Mario', 'Villanueva', '09261234567', 'Compostela, Cebu'),
    ('Teresa', 'Navarro', '09271234567', 'Talisay City, Cebu'),
    ('Carlos', 'Ramos', '09281234567', 'Consolacion, Cebu'),
    ('Liza', 'Torres', '09291234567', 'Dalaguete, Cebu'),
    ('Roberto', 'Castillo', '09301234567', 'Barili, Cebu'),
    ('Susan', 'Aquino', '09311234567', 'Sibonga, Cebu');

-- =====================================================
-- 15 FARMS (farmer_id 1 to 15, in the same order as the farmers)
-- =====================================================

INSERT INTO farm (farmer_id, farm_name, location, area_hectares, soil_type_id, irrigation_type_id)
VALUES
    (1, 'Dela Cruz Farm', 'Cebu City', 2.50, 1, 1),
    (2, 'Santos Rice Farm', 'Carcar City, Cebu', 4.20, 3, 2),
    (3, 'Reyes Agricultural Farm', 'Danao City, Cebu', 3.80, 4, 1),
    (4, 'Garcia Vegetable Farm', 'Argao, Cebu', 1.75, 1, 3),
    (5, 'Mendoza Farm', 'Bogo City, Cebu', 5.00, 2, 2),
    (6, 'Flores Corn Farm', 'Toledo City, Cebu', 3.40, 4, 1),
    (7, 'Cruz Family Farm', 'Naga City, Cebu', 2.80, 1, 2),
    (8, 'Lim Agricultural Farm', 'Minglanilla, Cebu', 2.25, 3, 3),
    (9, 'Fernandez Farm', 'Liloan, Cebu', 3.10, 4, 1),
    (10, 'Villanueva Green Farm', 'Compostela, Cebu', 4.50, 1, 2),
    (11, 'Navarro Rice Field', 'Talisay City, Cebu', 3.75, 2, 2),
    (12, 'Ramos Farm', 'Consolacion, Cebu', 2.60, 1, 1),
    (13, 'Torres Vegetable Farm', 'Dalaguete, Cebu', 1.80, 4, 3),
    (14, 'Castillo Agricultural Farm', 'Barili, Cebu', 5.50, 3, 2),
    (15, 'Aquino Farm', 'Sibonga, Cebu', 3.20, 1, 1);

-- =====================================================
-- 10 CROPS
-- =====================================================

INSERT INTO crop (crop_name, variety, category_id, growing_period_days, recommended_season_id)
VALUES
    ('Rice', 'NSIC Rc 222', 1, 115, 1),
    ('Corn', 'Yellow Corn', 1, 100, 2),
    ('Tomato', 'Diamante Max', 2, 90, 2),
    ('Eggplant', 'Morena', 2, 100, 3),
    ('Banana', 'Lakatan', 3, 300, 3),
    ('Mango', 'Carabao', 3, 150, 2),
    ('Peanut', 'BPI Pn 9', 4, 110, 2),
    ('Cassava', 'Golden Yellow', 5, 240, 3),
    ('Sweet Potato', 'NSIC Sp 30', 5, 120, 3),
    ('Watermelon', 'Sweet 16', 3, 85, 2);

-- =====================================================
-- 50 PLANTING RECORDS (farm_id = (i mod 15) + 1, crop_id = (i mod 10) + 1)
-- =====================================================

INSERT INTO planting (farm_id, crop_id, planting_date, area_planted, seed_quantity, expected_harvest_date, status_id)
VALUES
    (1, 1, '2026-01-10', 0.50, 10.00, '2026-05-05', 2),
    (2, 2, '2026-01-13', 0.60, 15.00, '2026-04-23', 2),
    (3, 3, '2026-01-16', 0.70, 20.00, '2026-04-16', 2),
    (4, 4, '2026-01-19', 0.80, 25.00, '2026-04-29', 2),
    (5, 5, '2026-01-22', 0.90, 30.00, '2026-11-18', 2),
    (6, 6, '2026-01-25', 1.00, 35.00, '2026-06-24', 2),
    (7, 7, '2026-01-28', 1.10, 40.00, '2026-05-18', 2),
    (8, 8, '2026-01-31', 1.20, 45.00, '2026-09-28', 2),
    (9, 9, '2026-02-03', 1.30, 10.00, '2026-06-03', 2),
    (10, 10, '2026-02-06', 1.40, 15.00, '2026-05-02', 2),
    (11, 1, '2026-02-09', 0.50, 20.00, '2026-06-04', 2),
    (12, 2, '2026-02-12', 0.60, 25.00, '2026-05-23', 2),
    (13, 3, '2026-02-15', 0.70, 30.00, '2026-05-16', 2),
    (14, 4, '2026-02-18', 0.80, 35.00, '2026-05-29', 2),
    (15, 5, '2026-02-21', 0.90, 40.00, '2026-12-18', 2),
    (1, 6, '2026-02-24', 1.00, 45.00, '2026-07-24', 2),
    (2, 7, '2026-02-27', 1.10, 10.00, '2026-06-17', 2),
    (3, 8, '2026-03-02', 1.20, 15.00, '2026-10-28', 2),
    (4, 9, '2026-03-05', 1.30, 20.00, '2026-07-03', 2),
    (5, 10, '2026-03-08', 1.40, 25.00, '2026-06-01', 2),
    (6, 1, '2026-03-11', 0.50, 30.00, '2026-07-04', 1),
    (7, 2, '2026-03-14', 0.60, 35.00, '2026-06-22', 1),
    (8, 3, '2026-03-17', 0.70, 40.00, '2026-06-15', 1),
    (9, 4, '2026-03-20', 0.80, 45.00, '2026-06-28', 1),
    (10, 5, '2026-03-23', 0.90, 10.00, '2027-01-17', 1),
    (11, 6, '2026-03-26', 1.00, 15.00, '2026-08-23', 1),
    (12, 7, '2026-03-29', 1.10, 20.00, '2026-07-17', 1),
    (13, 8, '2026-04-01', 1.20, 25.00, '2026-11-27', 1),
    (14, 9, '2026-04-04', 1.30, 30.00, '2026-08-02', 1),
    (15, 10, '2026-04-07', 1.40, 35.00, '2026-07-01', 1),
    (1, 1, '2026-04-10', 0.50, 40.00, '2026-08-03', 1),
    (2, 2, '2026-04-13', 0.60, 45.00, '2026-07-22', 1),
    (3, 3, '2026-04-16', 0.70, 10.00, '2026-07-15', 1),
    (4, 4, '2026-04-19', 0.80, 15.00, '2026-07-28', 1),
    (5, 5, '2026-04-22', 0.90, 20.00, '2027-02-16', 1),
    (6, 6, '2026-04-25', 1.00, 25.00, '2026-09-22', 1),
    (7, 7, '2026-04-28', 1.10, 30.00, '2026-08-16', 1),
    (8, 8, '2026-05-01', 1.20, 35.00, '2026-12-27', 1),
    (9, 9, '2026-05-04', 1.30, 40.00, '2026-09-01', 1),
    (10, 10, '2026-05-07', 1.40, 45.00, '2026-07-31', 1),
    (11, 1, '2026-05-10', 0.50, 10.00, '2026-09-02', 1),
    (12, 2, '2026-05-13', 0.60, 15.00, '2026-08-21', 1),
    (13, 3, '2026-05-16', 0.70, 20.00, '2026-08-14', 1),
    (14, 4, '2026-05-19', 0.80, 25.00, '2026-08-27', 1),
    (15, 5, '2026-05-22', 0.90, 30.00, '2027-03-18', 1),
    (1, 6, '2026-05-25', 1.00, 35.00, '2026-10-22', 1),
    (2, 7, '2026-05-28', 1.10, 40.00, '2026-09-15', 1),
    (3, 8, '2026-05-31', 1.20, 45.00, '2027-01-26', 1),
    (4, 9, '2026-06-03', 1.30, 10.00, '2026-10-01', 1),
    (5, 10, '2026-06-06', 1.40, 15.00, '2026-08-30', 1);

-- =====================================================
-- 20 HARVEST RECORDS (planting_id 1 to 20)
-- remarks_id: 1 = Good Harvest (old 'High quality harvest' and 'Good harvest'), 2 = Acceptable Quality
-- =====================================================

INSERT INTO harvest (planting_id, harvest_date, quantity_kg, selling_price_per_kg, quality_grade_id, remarks_id)
VALUES
    (1, '2026-05-07', 900.00, 22.00, 1, 1),
    (2, '2026-04-25', 1025.00, 25.50, 2, 1),
    (3, '2026-04-18', 1150.00, 29.00, 3, 2),
    (4, '2026-05-01', 1275.00, 32.50, 1, 1),
    (5, '2026-11-20', 1400.00, 36.00, 2, 1),
    (6, '2026-06-26', 1525.00, 39.50, 3, 2),
    (7, '2026-05-20', 1650.00, 43.00, 1, 1),
    (8, '2026-09-30', 1775.00, 46.50, 2, 1),
    (9, '2026-06-05', 1900.00, 22.00, 3, 2),
    (10, '2026-05-04', 2025.00, 25.50, 1, 1),
    (11, '2026-06-06', 2150.00, 29.00, 2, 1),
    (12, '2026-05-25', 2275.00, 32.50, 3, 2),
    (13, '2026-05-18', 2400.00, 36.00, 1, 1),
    (14, '2026-05-31', 2525.00, 39.50, 2, 1),
    (15, '2026-12-20', 2650.00, 43.00, 3, 2),
    (16, '2026-07-26', 2775.00, 46.50, 1, 1),
    (17, '2026-06-19', 2900.00, 22.00, 2, 1),
    (18, '2026-10-30', 3025.00, 25.50, 3, 2),
    (19, '2026-07-05', 3150.00, 29.00, 1, 1),
    (20, '2026-06-03', 3275.00, 32.50, 2, 1);

-- =====================================================
-- RECORD COUNTS (should be: farmer 15, farm 15, crop 10, planting 50, harvest 20)
-- =====================================================

SELECT 'farmer' AS table_name, COUNT(*) AS records FROM farmer
UNION ALL SELECT 'farm', COUNT(*) FROM farm
UNION ALL SELECT 'crop', COUNT(*) FROM crop
UNION ALL SELECT 'planting', COUNT(*) FROM planting
UNION ALL SELECT 'harvest', COUNT(*) FROM harvest;