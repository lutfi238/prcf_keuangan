import { NextResponse } from 'next/server'

export async function POST() {
  const response = NextResponse.json({
    message: 'Logged out successfully',
    redirectUrl: '/auth/login'
  })

  // Clear the authentication cookies
  response.cookies.set('token', '', {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'strict',
    maxAge: 0,
    path: '/'
  })

  response.cookies.set('otp_token', '', {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'strict',
    maxAge: 0,
    path: '/'
  })

  return response
}
