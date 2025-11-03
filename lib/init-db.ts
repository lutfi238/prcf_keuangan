import { sql } from '@vercel/postgres'
import bcrypt from 'bcryptjs'

// Database initialization script
export async function initDatabase() {
  try {
    console.log('Initializing database...')

    // Create tables
    console.log('Creating tables...')
    await sql`
      ${`
        -- Users table
        CREATE TABLE IF NOT EXISTS "user" (
          id_user SERIAL PRIMARY KEY,
          nama VARCHAR(100) NOT NULL,
          email VARCHAR(100) UNIQUE NOT NULL,
          no_hp VARCHAR(20),
          password_hash VARCHAR(255) NOT NULL,
          role VARCHAR(50) NOT NULL CHECK (role IN ('Admin', 'Project Manager', 'Staff Accountant', 'Finance Manager', 'Direktur')),
          status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'pending')),
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Projects table
        CREATE TABLE IF NOT EXISTS projects (
          kode_proyek VARCHAR(20) PRIMARY KEY,
          nama_proyek VARCHAR(200) NOT NULL,
          deskripsi TEXT,
          status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'completed')),
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
      `}
    `

    // Check if admin user exists
    const adminCheck = await sql`
      SELECT id_user FROM "user" WHERE email = 'admin@prcf.com' LIMIT 1
    `

    if (adminCheck.rows.length === 0) {
      console.log('Creating default admin user...')

      // Create default admin user
      const hashedPassword = await bcrypt.hash('admin123', 12)

      await sql`
        INSERT INTO "user" (nama, email, no_hp, password_hash, role, status)
        VALUES ('Administrator', 'admin@prcf.com', '6281234567890', ${hashedPassword}, 'Admin', 'active')
      `

      console.log('Default admin user created:')
      console.log('Email: admin@prcf.com')
      console.log('Password: admin123')
    }

    // Create sample projects
    const projectCheck = await sql`
      SELECT kode_proyek FROM projects WHERE kode_proyek = 'PRJ-2025-001' LIMIT 1
    `

    if (projectCheck.rows.length === 0) {
      console.log('Creating sample projects...')

      await sql`
        INSERT INTO projects (kode_proyek, nama_proyek, deskripsi, status)
        VALUES
          ('PRJ-2025-001', 'Forest Conservation Project 2025', 'Forest conservation and monitoring project for 2025', 'active'),
          ('PRJ-2025-002', 'Community Development Program', 'Community development and capacity building program', 'active')
      `
    }

    console.log('Database initialization completed!')
  } catch (error) {
    console.error('Database initialization error:', error)
    throw error
  }
}
