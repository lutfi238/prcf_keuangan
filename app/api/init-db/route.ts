import { NextResponse } from 'next/server'
import { initDatabase } from '@/lib/init-db'

export async function POST() {
  try {
    // Only allow in development or with proper authorization
    if (process.env.NODE_ENV === 'production') {
      return NextResponse.json(
        { message: 'Database initialization not allowed in production' },
        { status: 403 }
      )
    }

    await initDatabase()

    return NextResponse.json({
      message: 'Database initialized successfully',
      adminCredentials: {
        email: 'admin@prcf.com',
        password: 'admin123'
      }
    })
  } catch (error) {
    console.error('Database initialization error:', error)
    return NextResponse.json(
      { message: 'Database initialization failed', error: error.message },
      { status: 500 }
    )
  }
}
