-- Cardinal Stage Database Schema
-- Run these statements in Supabase SQL Editor

-- ====================================
-- 1. ROLES TABLE (Role Definitions)
-- ====================================
CREATE TABLE IF NOT EXISTS roles (
  id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default roles
INSERT INTO roles (name, description) VALUES
  ('system_admin', 'COO - System administrator with full control'),
  ('org_admin', 'Organization President - manages organization profile and bookings'),
  ('organizer', 'Event Organizer - creates and manages booking requests')
ON CONFLICT (name) DO NOTHING;

-- ====================================
-- 2. ORGANIZATIONS TABLE
-- ====================================
CREATE TABLE IF NOT EXISTS organizations (
  id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
  name VARCHAR(255) NOT NULL,
  bio TEXT,
  genre VARCHAR(100),
  technical_requirements TEXT,
  youtube_links TEXT,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ====================================
-- 3. USERS TABLE (Extended Profile)
-- ====================================
CREATE TABLE IF NOT EXISTS users_extended (
  id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
  role_id BIGINT NOT NULL REFERENCES roles(id),
  org_id BIGINT REFERENCES organizations(id),
  full_name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create index on role_id for faster queries
CREATE INDEX IF NOT EXISTS idx_users_role_id ON users_extended(role_id);
CREATE INDEX IF NOT EXISTS idx_users_org_id ON users_extended(org_id);
CREATE INDEX IF NOT EXISTS idx_users_email ON users_extended(email);

-- ====================================
-- 4. BOOKING REQUESTS TABLE
-- ====================================
CREATE TABLE IF NOT EXISTS booking_requests (
  id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
  organizer_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  organization_id BIGINT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
  event_name VARCHAR(255) NOT NULL,
  event_date DATE NOT NULL,
  venue VARCHAR(255) NOT NULL,
  technical_needs TEXT,
  status VARCHAR(50) DEFAULT 'pending', -- pending, accepted, declined
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for faster queries
CREATE INDEX IF NOT EXISTS idx_bookings_organizer ON booking_requests(organizer_id);
CREATE INDEX IF NOT EXISTS idx_bookings_organization ON booking_requests(organization_id);
CREATE INDEX IF NOT EXISTS idx_bookings_status ON booking_requests(status);

-- ====================================
-- 5. AUDIT LOG TABLE
-- ====================================
CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
  user_id UUID REFERENCES auth.users(id) ON DELETE SET NULL,
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(50) NOT NULL,
  entity_id BIGINT,
  old_values JSONB,
  new_values JSONB,
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create index for faster audit log queries
CREATE INDEX IF NOT EXISTS idx_audit_user ON audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_created_at ON audit_logs(created_at DESC);

-- ====================================
-- 6. SETUP: Enable Row Level Security (RLS)
-- ====================================

-- Users Extended RLS Policies
ALTER TABLE users_extended ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can view their own profile" ON users_extended
  FOR SELECT USING (auth.uid() = id);

CREATE POLICY "System admins can view all users" ON users_extended
  FOR SELECT USING (
    EXISTS (
      SELECT 1 FROM users_extended 
      WHERE id = auth.uid() 
      AND role_id = (SELECT id FROM roles WHERE name = 'system_admin')
    )
  );

-- Organizations RLS Policies
ALTER TABLE organizations ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Anyone can read active organizations" ON organizations
  FOR SELECT USING (is_active = TRUE);

CREATE POLICY "Org admins can update their organization" ON organizations
  FOR UPDATE USING (
    EXISTS (
      SELECT 1 FROM users_extended
      WHERE id = auth.uid()
      AND org_id = organizations.id
      AND role_id = (SELECT id FROM roles WHERE name = 'org_admin')
    )
  );

CREATE POLICY "System admins can manage all organizations" ON organizations
  FOR ALL USING (
    EXISTS (
      SELECT 1 FROM users_extended
      WHERE id = auth.uid()
      AND role_id = (SELECT id FROM roles WHERE name = 'system_admin')
    )
  );

-- Booking Requests RLS Policies
ALTER TABLE booking_requests ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Organizers can view their own bookings" ON booking_requests
  FOR SELECT USING (
    organizer_id = auth.uid() 
    OR EXISTS (
      SELECT 1 FROM users_extended 
      WHERE id = auth.uid() 
      AND org_id = booking_requests.organization_id
      AND role_id = (SELECT id FROM roles WHERE name = 'org_admin')
    )
  );

CREATE POLICY "System admins can view all bookings" ON booking_requests
  FOR SELECT USING (
    EXISTS (
      SELECT 1 FROM users_extended 
      WHERE id = auth.uid() 
      AND role_id = (SELECT id FROM roles WHERE name = 'system_admin')
    )
  );

-- Audit Logs RLS Policies
ALTER TABLE audit_logs ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Only system admins can view audit logs" ON audit_logs
  FOR SELECT USING (
    EXISTS (
      SELECT 1 FROM users_extended
      WHERE id = auth.uid()
      AND role_id = (SELECT id FROM roles WHERE name = 'system_admin')
    )
  );
