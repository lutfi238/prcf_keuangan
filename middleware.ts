import { NextResponse } from 'next/server'
import type { NextRequest } from 'next/server'
import jwt from 'jsonwebtoken'

const JWT_SECRET = process.env.JWT_SECRET || 'your-secret-key'

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl

  // Public paths that don't require authentication
  const publicPaths = ['/auth/login', '/auth/register', '/auth/forgot-password', '/api/auth/login', '/api/auth/register']
  const isPublicPath = publicPaths.some(path => pathname.startsWith(path))

  if (isPublicPath) {
    return NextResponse.next()
  }

  // Check for JWT token in cookies
  const token = request.cookies.get('token')?.value

  if (!token) {
    return NextResponse.redirect(new URL('/auth/login', request.url))
  }

  try {
    const decoded = jwt.verify(token, JWT_SECRET) as any

    // Role-based redirects for root path
    if (pathname === '/') {
      switch (decoded.role) {
        case 'Admin':
          return NextResponse.redirect(new URL('/admin', request.url))
        case 'Project Manager':
          return NextResponse.redirect(new URL('/dashboard/pm', request.url))
        case 'Staff Accountant':
          return NextResponse.redirect(new URL('/dashboard/sa', request.url))
        case 'Finance Manager':
          return NextResponse.redirect(new URL('/dashboard/fm', request.url))
        case 'Direktur':
          return NextResponse.redirect(new URL('/dashboard/dir', request.url))
        default:
          return NextResponse.redirect(new URL('/auth/login', request.url))
      }
    }

    return NextResponse.next()
  } catch (error) {
    return NextResponse.redirect(new URL('/auth/login', request.url))
  }
}

export const config = {
  matcher: [
    /*
     * Match all request paths except for the ones starting with:
     * - api (API routes)
     * - _next/static (static files)
     * - _next/image (image optimization files)
     * - favicon.ico (favicon file)
     */
    '/((?!api|_next/static|_next/image|favicon.ico).*)',
  ],
}
