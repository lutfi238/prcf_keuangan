import { NextRequest, NextResponse } from 'next/server'
import bcrypt from 'bcryptjs'
import jwt from 'jsonwebtoken'
import { sql } from '@vercel/postgres'
import nodemailer from 'nodemailer'

const JWT_SECRET = process.env.JWT_SECRET || 'your-secret-key'

export async function POST(request: NextRequest) {
  try {
    const { identifier, password } = await request.json()

    if (!identifier || !password) {
      return NextResponse.json(
        { message: 'Email/nomor HP dan password diperlukan' },
        { status: 400 }
      )
    }

    const emailIdentifier = identifier.toLowerCase()
    const phoneIdentifier = /^\d/.test(identifier) ? identifier.replace(/\D/g, '') : null

    // Query user by email or phone
    let user
    if (phoneIdentifier) {
      const result = await sql`
        SELECT * FROM user WHERE no_hp = ${phoneIdentifier}
      `
      user = result.rows[0]
    } else {
      const result = await sql`
        SELECT * FROM user WHERE email = ${emailIdentifier}
      `
      user = result.rows[0]
    }

    if (!user) {
      return NextResponse.json(
        { message: 'Email/nomor atau password salah' },
        { status: 401 }
      )
    }

    // Check account status
    if (user.status === 'inactive') {
      return NextResponse.json(
        { message: 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.' },
        { status: 403 }
      )
    }

    if (user.status === 'pending') {
      // Create JWT for pending user
      const token = jwt.sign(
        {
          id: user.id_user,
          email: user.email,
          role: user.role,
          status: 'pending'
        },
        JWT_SECRET,
        { expiresIn: '24h' }
      )

      const response = NextResponse.json({
        message: 'Akun pending approval',
        redirectUrl: '/auth/account-pending'
      })

      response.cookies.set('token', token, {
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'strict',
        maxAge: 60 * 60 * 24 // 24 hours
      })

      return response
    }

    // Verify password
    const isValidPassword = await bcrypt.compare(password, user.password_hash)
    if (!isValidPassword) {
      return NextResponse.json(
        { message: 'Email/nomor atau password salah' },
        { status: 401 }
      )
    }

    // Check developer mode and OTP bypass
    const isDeveloper = process.env.DEVELOPER_MODE === 'true' &&
                       process.env.DEVELOPER_EMAILS?.split(',').includes(user.email)
    const skipAllOtp = process.env.SKIP_OTP_FOR_ALL === 'true'

    if (isDeveloper || skipAllOtp) {
      // Direct login bypass
      const token = jwt.sign(
        {
          id: user.id_user,
          email: user.email,
          role: user.role,
          name: user.nama
        },
        JWT_SECRET,
        { expiresIn: '7d' }
      )

      let redirectUrl = '/dashboard'
      switch (user.role) {
        case 'Admin':
          redirectUrl = '/admin'
          break
        case 'Project Manager':
          redirectUrl = '/dashboard/pm'
          break
        case 'Staff Accountant':
          redirectUrl = '/dashboard/sa'
          break
        case 'Finance Manager':
          redirectUrl = '/dashboard/fm'
          break
        case 'Direktur':
          redirectUrl = '/dashboard/dir'
          break
      }

      const response = NextResponse.json({
        message: 'Login berhasil',
        redirectUrl
      })

      response.cookies.set('token', token, {
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'strict',
        maxAge: 60 * 60 * 24 * 7 // 7 days
      })

      return response
    }

    // Generate OTP for normal users
    const otp = Math.floor(100000 + Math.random() * 900000).toString()

    // Store OTP in session (in production, use Redis or database)
    const otpToken = jwt.sign(
      {
        otp,
        userId: user.id_user,
        email: user.email,
        role: user.role,
        name: user.nama,
        exp: Math.floor(Date.now() / 1000) + (60 * 5) // 5 minutes
      },
      JWT_SECRET
    )

    // Send OTP email
    if (process.env.EMAIL_OTP_ENABLED !== 'false') {
      try {
        const transporter = nodemailer.createTransport({
          host: process.env.SMTP_HOST,
          port: parseInt(process.env.SMTP_PORT || '587'),
          secure: false,
          auth: {
            user: process.env.SMTP_USER,
            pass: process.env.SMTP_PASS
          }
        })

        const mailOptions = {
          from: `${process.env.FROM_NAME} <${process.env.FROM_EMAIL}>`,
          to: user.email,
          subject: 'Kode OTP Login - PRCF INDONESIA Financial',
          html: `
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f3f4f6; padding: 20px; }
                    .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%); color: white; padding: 30px; text-align: center; }
                    .content { padding: 30px; }
                    .otp-box { background: #EFF6FF; border: 2px solid #3B82F6; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
                    .otp-code { font-size: 36px; font-weight: bold; color: #3B82F6; letter-spacing: 10px; font-family: monospace; }
                    .warning { background: #FEF3C7; border-left: 4px solid #F59E0B; padding: 15px; margin: 15px 0; border-radius: 4px; }
                    .footer { text-align: center; color: #6B7280; font-size: 12px; padding: 20px; background: #F9FAFB; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1 style="margin: 0;">🔐 Kode OTP Anda</h1>
                        <p style="margin: 10px 0 0 0;">PRCF INDONESIA Financial Management System</p>
                    </div>
                    <div class="content">
                        <h2 style="color: #1F2937;">Halo!</h2>
                        <p>Anda menerima email ini karena ada permintaan login ke sistem PRCF INDONESIA Financial.</p>

                        <div class="otp-box">
                            <p style="margin: 0 0 10px 0; font-size: 14px; color: #6B7280;">Kode OTP Anda:</p>
                            <div class="otp-code">${otp}</div>
                        </div>

                        <div class="warning">
                            <strong>⏱️ Penting:</strong> Kode ini hanya berlaku selama <strong>5 menit</strong>.<br>
                            🔒 Jangan bagikan kode ini kepada siapapun!
                        </div>

                        <p style="color: #6B7280; font-size: 14px;">Jika Anda tidak melakukan permintaan ini, abaikan email ini atau hubungi administrator.</p>
                    </div>
                    <div class="footer">
                        <p>Email ini dikirim secara otomatis, mohon tidak membalas.</p>
                        <p>&copy; ${new Date().getFullYear()} PRCF INDONESIA Financial. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
          `
        }

        await transporter.sendMail(mailOptions)

        const response = NextResponse.json({
          message: 'Kode OTP telah dikirim ke email Anda',
          email: user.email,
          requiresOTP: true
        })

        // Store OTP token in cookie
        response.cookies.set('otp_token', otpToken, {
          httpOnly: true,
          secure: process.env.NODE_ENV === 'production',
          sameSite: 'strict',
          maxAge: 60 * 5 // 5 minutes
        })

        return response

      } catch (emailError) {
        console.error('Email sending error:', emailError)
        return NextResponse.json(
          { message: 'Gagal mengirim OTP email. Silakan coba lagi.' },
          { status: 500 }
        )
      }
    } else {
      return NextResponse.json(
        { message: 'OTP email saat ini tidak tersedia. Hubungi administrator.' },
        { status: 503 }
      )
    }

  } catch (error) {
    console.error('Login error:', error)
    return NextResponse.json(
      { message: 'Terjadi kesalahan server' },
      { status: 500 }
    )
  }
}
