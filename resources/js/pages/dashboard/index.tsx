import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { BarChart3, MessageSquare, CheckCircle, XCircle, Smartphone } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import { BreadcrumbItem } from "@/types"
import { Head, usePage } from "@inertiajs/react"


interface RecipientCounts {
  successful: number;
  failed: number;
  total: number;
}

interface Analytics {
  messageStats: Array<{ type: string; count: string }>
  recipientStats: Array<{ type: string; status: string; count: string }>
  recentMessages: Array<{
    id: number
    content: string
    type: string
    created_at: string
    recipient_count: string
    success_count: string
    failed_count: string
  }>
  recipientCounts:RecipientCounts;
}

type PageProps = {
  analytics: Analytics
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
  },
];

export default function Dashboard() {
  const { props } = usePage<PageProps>();
  const { analytics } = props

  // Calculate totals with safe defaults
  const totalMessages = analytics.messageStats?.reduce((sum, stat) => sum + Number.parseInt(stat.count || "0"), 0) || 0
  const totalRecipients = analytics.recipientCounts.total ||0
    // analytics.recipientStats?.reduce((sum, stat) => sum + Number.parseInt(stat.count || "0"), 0) || 0
  const successfulRecipients = analytics.recipientCounts.successful ||0
    // analytics.recipientStats
    //   ?.filter((stat) => stat.status === "sent" || stat.status === "success")
    //   .reduce((sum, stat) => sum + Number.parseInt(stat.count || "0"), 0) || 0
  const failedRecipients = analytics.recipientCounts.failed ||0
    // analytics.recipientStats
    //   ?.filter((stat) => stat.status === "failed")
    //   .reduce((sum, stat) => sum + Number.parseInt(stat.count || "0"), 0) || 0
  const smsCount =
    analytics.messageStats
      ?.find((stat) => stat.type === "sms")?.count || "0"
  const whatsappCount =
    analytics.messageStats
      ?.find((stat) => stat.type === "whatsapp")?.count || "0"

  // Calculate success rate
  const successRate = totalRecipients > 0
    ? Math.round((successfulRecipients / totalRecipients) * 100)
    : 0

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Dashboard" />
      <div className="px-4 w-full mx-auto space-y-6">
        <div className="flex items-center space-x-2">
          <BarChart3 className="h-6 w-6" />
          <h1 className="text-2xl font-bold">Dashboard</h1>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Total Messages</p>
                  <p className="text-2xl font-bold">{totalMessages}</p>
                </div>
                <MessageSquare className="h-8 w-8 text-blue-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Successful</p>
                  <p className="text-2xl font-bold text-green-600">{successfulRecipients}</p>
                </div>
                <CheckCircle className="h-8 w-8 text-green-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Failed</p>
                  <p className="text-2xl font-bold text-red-600">{failedRecipients}</p>
                </div>
                <XCircle className="h-8 w-8 text-red-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Total Recipients</p>
                  <p className="text-2xl font-bold">{totalRecipients}</p>
                </div>
                <Smartphone className="h-8 w-8 text-purple-600" />
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Message Type Breakdown */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <Card>
            <CardHeader>
              <CardTitle>Message Types</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <span className="text-sm font-medium">SMS Messages</span>
                  <Badge variant="outline">{smsCount}</Badge>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-sm font-medium">WhatsApp Messages</span>
                  <Badge variant="outline">{whatsappCount}</Badge>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Success Rate</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <span className="text-sm font-medium">Success Rate</span>
                  <Badge variant="outline" className="text-green-600">
                    {successRate}%
                  </Badge>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2">
                  <div
                    className="bg-green-600 h-2 rounded-full"
                    style={{
                      width: `${successRate}%`,
                    }}
                  ></div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Recent Messages */}
        <Card>
          <CardHeader>
            <CardTitle>Recent Messages</CardTitle>
          </CardHeader>
          <CardContent>
            {analytics.recentMessages && analytics.recentMessages.length > 0 ? (
              <div className="space-y-4">
                {analytics.recentMessages.map((message) => {
                  // Determine message status based on success/failure counts
                  const status = Number(message.failed_count) === 0
                    ? "sent"
                    : Number(message.success_count) === 0
                      ? "failed"
                      : "partial"

                  return (
                    <div key={message.id} className="flex items-start justify-between p-4 border rounded-lg">
                      <div className="flex-1">
                        <p className="font-medium truncate">{message.content || "No content"}</p>
                        <div className="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                          <span>Type: {(message.type || "unknown").toUpperCase()}</span>
                          <span>Recipients: {message.recipient_count || 0}</span>
                          <span>Success: {message.success_count || 0}</span>
                          <span>Failed: {message.failed_count || 0}</span>
                          <span>
                            {message.created_at ? new Date(message.created_at).toLocaleDateString() : "Unknown date"}
                          </span>
                        </div>
                      </div>
                      <Badge
                        variant={
                          status === "sent"
                            ? "default"
                            : status === "failed"
                              ? "destructive"
                              : "secondary"
                        }
                      >
                        {status}
                      </Badge>
                    </div>
                  )
                })}
              </div>
            ) : (
              <div className="text-center text-gray-500 py-8">
                No messages found. Send your first message to see analytics here.
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  )
}
