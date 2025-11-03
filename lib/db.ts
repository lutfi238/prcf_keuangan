import { sql } from '@vercel/postgres'

// Database connection utility
export { sql }

// Helper function to execute queries with error handling
// Note: Vercel Postgres uses tagged template literals, not raw query strings
export async function executeQuery(template: TemplateStringsArray, ...values: any[]) {
  try {
    const result = await sql(template, ...values)
    return result
  } catch (error) {
    console.error('Database query error:', error)
    throw error
  }
}

// Helper function to get user by ID
export async function getUserById(id: number) {
  try {
    const result = await sql`
      SELECT id_user, nama, email, no_hp, role, status, created_at
      FROM "user"
      WHERE id_user = ${id}
    `
    return result.rows[0] || null
  } catch (error) {
    console.error('Error getting user by ID:', error)
    throw error
  }
}

// Helper function to get user by email
export async function getUserByEmail(email: string) {
  try {
    const result = await sql`
      SELECT id_user, nama, email, no_hp, password_hash, role, status, created_at
      FROM "user"
      WHERE email = ${email.toLowerCase()}
    `
    return result.rows[0] || null
  } catch (error) {
    console.error('Error getting user by email:', error)
    throw error
  }
}

// Helper function to get user by phone
export async function getUserByPhone(phone: string) {
  try {
    // Clean phone number (remove all non-digits)
    const cleanPhone = phone.replace(/\D/g, '')
    const result = await sql`
      SELECT id_user, nama, email, no_hp, password_hash, role, status, created_at
      FROM "user"
      WHERE no_hp = ${cleanPhone}
    `
    return result.rows[0] || null
  } catch (error) {
    console.error('Error getting user by phone:', error)
    throw error
  }
}
