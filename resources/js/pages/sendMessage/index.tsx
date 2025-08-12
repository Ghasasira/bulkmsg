import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Textarea } from "@/components/ui/textarea"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Checkbox } from "@/components/ui/checkbox"
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import { Input } from "@/components/ui/input"
import { Loader2, Send, Users, MessageSquare } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import { Head, usePage, useForm } from "@inertiajs/react"
import { BreadcrumbItem } from "@/types"

interface User {
  id: number
  name: string
  phone: string
  email: string
}

type PageProps = {
  users: User[]
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
  },
  {
    title: 'Send Message',
    href: '/messages/create',
  },
];

export default function SendMessage() {
  const { users } = usePage<PageProps>().props
  const [selectedUsers, setSelectedUsers] = useState<number[]>([])
  const [searchTerm, setSearchTerm] = useState("")

  const { data, setData, post, processing, errors, reset } = useForm({
    content: '',
    type: 'sms' as 'sms' | 'whatsapp',
    recipients: [] as number[],
  })

  const filteredUsers = users.filter(
    (user) => user.name.toLowerCase().includes(searchTerm.toLowerCase()) || user.phone.includes(searchTerm),
  )

  const handleUserToggle = (userId: number) => {
    const newSelected = selectedUsers.includes(userId)
      ? selectedUsers.filter((id) => id !== userId)
      : [...selectedUsers, userId]

    setSelectedUsers(newSelected)
    setData('recipients', newSelected)
  }

  const selectAll = () => {
    const allIds = filteredUsers.map((user) => user.id)
    setSelectedUsers(allIds)
    setData('recipients', allIds)
  }

  const deselectAll = () => {
    setSelectedUsers([])
    setData('recipients', [])
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()

    post(route("messages.send"), {
      onSuccess: () => {
        reset()
        setSelectedUsers([])
      },
    })
  }

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Send Message" />
      <div className="px-4 w-full mx-auto space-y-6">
        <div className="flex items-center space-x-2">
          <MessageSquare className="h-6 w-6" />
          <h1 className="text-2xl font-bold">Send Message</h1>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Message Composition */}
            <Card>
              <CardHeader>
                <CardTitle>Compose Message</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <Label htmlFor="message-type">Message Type</Label>
                  <RadioGroup
                    value={data.type}
                    onValueChange={(value: "sms" | "whatsapp") => setData('type', value)}
                    className="flex space-x-4 mt-2"
                  >
                    <div className="flex items-center space-x-2">
                      <RadioGroupItem value="sms" id="sms" />
                      <Label htmlFor="sms">SMS</Label>
                    </div>
                    <div className="flex items-center space-x-2">
                      <RadioGroupItem value="whatsapp" id="whatsapp" />
                      <Label htmlFor="whatsapp">WhatsApp</Label>
                    </div>
                  </RadioGroup>
                  {errors.type && <p className="text-sm text-red-600 mt-1">{errors.type}</p>}
                </div>

                <div>
                  <Label htmlFor="message">Message</Label>
                  <Textarea
                    id="message"
                    placeholder="Type your message here..."
                    value={data.content}
                    onChange={(e) => setData('content', e.target.value)}
                    rows={6}
                    className="mt-2"
                  />
                  <div className="text-sm text-gray-500 mt-1">{data.content.length} characters</div>
                  {errors.content && <p className="text-sm text-red-600 mt-1">{errors.content}</p>}
                </div>

                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-2">
                    <Users className="h-4 w-4" />
                    <span className="text-sm">{selectedUsers.length} recipient(s) selected</span>
                  </div>
                  <Button
                    type="submit"
                    disabled={processing || !data.content.trim() || selectedUsers.length === 0}
                    className="flex items-center space-x-2"
                  >
                    {processing ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
                    <span>Send Message</span>
                  </Button>
                </div>
                {errors.recipients && <p className="text-sm text-red-600">{errors.recipients}</p>}
              </CardContent>
            </Card>

            {/* Recipient Selection */}
            <Card>
              <CardHeader>
                <CardTitle>Select Recipients</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <Input
                    placeholder="Search users..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                  />
                </div>

                <div className="flex space-x-2">
                  <Button type="button" variant="outline" size="sm" onClick={selectAll}>
                    Select All
                  </Button>
                  <Button type="button" variant="outline" size="sm" onClick={deselectAll}>
                    Deselect All
                  </Button>
                </div>

                <div className="max-h-96 overflow-y-auto space-y-2">
                  {filteredUsers.map((user) => (
                    <div key={user.id} className="flex items-center space-x-3 p-3 border rounded-lg hover:bg-gray-50">
                      <Checkbox
                        checked={selectedUsers.includes(user.id)}
                        onCheckedChange={() => handleUserToggle(user.id)}
                      />
                      <div className="flex-1">
                        <div className="font-medium">{user.name}</div>
                        <div className="text-sm text-gray-500">{user.phone}</div>
                      </div>
                    </div>
                  ))}
                </div>

                {selectedUsers.length > 0 && (
                  <div className="border-t pt-4">
                    <div className="text-sm font-medium mb-2">Selected Recipients:</div>
                    <div className="flex flex-wrap gap-1">
                      {selectedUsers.map((userId) => {
                        const user = users.find((u) => u.id === userId)
                        return user ? (
                          <Badge key={userId} variant="secondary">
                            {user.name}
                          </Badge>
                        ) : null
                      })}
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </form>
      </div>
    </AppLayout>
  )
}
