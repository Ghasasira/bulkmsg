import { sql } from "@/lib/db"
import { NextResponse } from "next/server"

export async function GET() {
  try {
    // Get total messages by type and status
    const messageStats = await sql`
      SELECT 
        COALESCE(type, 'unknown') as type,
        COALESCE(status, 'unknown') as status,
        COUNT(*) as count
      FROM messages 
      GROUP BY type, status
    `

    // Get recipient stats
    const recipientStats = await sql`
      SELECT 
        COALESCE(m.type, 'unknown') as type,
        COALESCE(mr.status, 'unknown') as status,
        COUNT(*) as count
      FROM message_recipients mr
      JOIN messages m ON mr.message_id = m.id
      GROUP BY m.type, mr.status
    `

    // Get recent messages
    const recentMessages = await sql`
      SELECT 
        m.id,
        COALESCE(m.content, '') as content,
        COALESCE(m.type, 'unknown') as type,
        COALESCE(m.status, 'unknown') as status,
        COALESCE(m.created_at, NOW()) as created_at,
        COUNT(mr.id) as recipient_count
      FROM messages m
      LEFT JOIN message_recipients mr ON m.id = mr.message_id
      GROUP BY m.id, m.content, m.type, m.status, m.created_at
      ORDER BY m.created_at DESC
      LIMIT 10
    `

    // Ensure we return valid data even if tables are empty
    const response = {
      messageStats: messageStats || [],
      recipientStats: recipientStats || [],
      recentMessages: recentMessages || [],
    }

    return NextResponse.json(response)
  } catch (error) {
    console.error("Error fetching analytics:", error)

    // Return empty data structure instead of error to prevent frontend crashes
    return NextResponse.json({
      messageStats: [],
      recipientStats: [],
      recentMessages: [],
    })
  }
}
