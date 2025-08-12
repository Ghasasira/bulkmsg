import { sql } from "@/lib/db"
import { NextResponse } from "next/server"

export async function POST(request: Request) {
  try {
    const { content, type, recipients, scheduledAt } = await request.json()

    // Create the message
    const [message] = await sql`
      INSERT INTO messages (content, type, status, scheduled_at)
      VALUES (${content}, ${type}, ${scheduledAt ? "scheduled" : "pending"}, ${scheduledAt})
      RETURNING id
    `

    // Add recipients
    for (const userId of recipients) {
      await sql`
        INSERT INTO message_recipients (message_id, user_id)
        VALUES (${message.id}, ${userId})
      `
    }

    // Simulate sending messages (in real app, integrate with SMS/WhatsApp APIs)
    if (!scheduledAt) {
      // Simulate random success/failure for demo
      for (const userId of recipients) {
        const success = Math.random() > 0.1 // 90% success rate
        const status = success ? "sent" : "failed"
        const errorMessage = success ? null : "Network error"

        await sql`
          UPDATE message_recipients 
          SET status = ${status}, sent_at = ${success ? new Date().toISOString() : null}, error_message = ${errorMessage}
          WHERE message_id = ${message.id} AND user_id = ${userId}
        `
      }

      await sql`
        UPDATE messages 
        SET status = 'sent', sent_at = ${new Date().toISOString()}
        WHERE id = ${message.id}
      `
    }

    return NextResponse.json({ success: true, messageId: message.id })
  } catch (error) {
    console.error("Error sending message:", error)
    return NextResponse.json({ error: "Failed to send message" }, { status: 500 })
  }
}
