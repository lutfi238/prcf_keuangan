import { redirect } from 'next/navigation'
import { cookies } from 'next/headers'

export default function Home() {
  // This will be handled by middleware for authentication checks
  // For now, redirect to login
  redirect('/auth/login')
}
