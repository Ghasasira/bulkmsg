import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Checkbox } from "@/components/ui/checkbox"
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Calendar, Clock, Loader2 } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import { BreadcrumbItem } from "@/types"
import { Head, usePage, useForm } from "@inertiajs/react"

interface User {
  id: number
  name: string
  phone: string
  email: string
}

type PageProps ={
  users: User[]
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
  },
  {
    title: 'Schedule',
    href: '/messages/schedule',
  },
];

export default function SchedulePage() {
  const { users } = usePage<PageProps>().props
  const [selectedUsers, setSelectedUsers] = useState<number[]>([])

  const { data, setData, post, processing, errors, reset } = useForm({
    content: '',
    type: 'sms' as 'sms' | 'whatsapp',
    recipients: [] as number[],
    scheduledAt: '',
  })

  const handleUserToggle = (userId: number) => {
    const newSelected = selectedUsers.includes(userId)
      ? selectedUsers.filter((id) => id !== userId)
      : [...selectedUsers, userId]

    setSelectedUsers(newSelected)
    setData('recipients', newSelected)
  }

  const selectAll = () => {
    const allIds = users.map((user) => user.id)
    setSelectedUsers(allIds)
    setData('recipients', allIds)
  }

  const deselectAll = () => {
    setSelectedUsers([])
    setData('recipients', [])
  }

  const handleDateTimeChange = (date: string, time: string) => {
    if (date && time) {
      const scheduledAt = new Date(`${date}T${time}`).toISOString()
      setData('scheduledAt', scheduledAt)
    } else {
      setData('scheduledAt', '')
    }
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()

    post('/messages/send', {
      onSuccess: () => {
        reset()
        setSelectedUsers([])
      },
    })
  }

  const [scheduledDate, setScheduledDate] = useState("")
  const [scheduledTime, setScheduledTime] = useState("")

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Schedule" />
      <div className="px-4 w-full mx-auto space-y-6">
        <div className="flex items-center space-x-2">
          <Calendar className="h-6 w-6" />
          <h1 className="text-2xl font-bold">Schedule Message</h1>
        </div>

        <form onSubmit={handleSubmit}>
          <Card>
            <CardHeader>
              <CardTitle>Schedule a Message</CardTitle>
            </CardHeader>
            <CardContent className="space-y-6">
              {/* Message Type */}
              <div>
                <Label>Message Type</Label>
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

              {/* Message Content */}
              <div>
                <Label htmlFor="message">Message</Label>
                <Textarea
                  id="message"
                  placeholder="Type your message here..."
                  value={data.content}
                  onChange={(e) => setData('content', e.target.value)}
                  rows={4}
                  className="mt-2"
                />
                <div className="text-sm text-gray-500 mt-1">{data.content.length} characters</div>
                {errors.content && <p className="text-sm text-red-600 mt-1">{errors.content}</p>}
              </div>

              {/* Schedule Date and Time */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <Label htmlFor="date">Date</Label>
                  <Input
                    id="date"
                    type="date"
                    value={scheduledDate}
                    onChange={(e) => {
                      setScheduledDate(e.target.value)
                      handleDateTimeChange(e.target.value, scheduledTime)
                    }}
                    min={new Date().toISOString().split("T")[0]}
                    className="mt-2"
                  />
                </div>
                <div>
                  <Label htmlFor="time">Time</Label>
                  <Input
                    id="time"
                    type="time"
                    value={scheduledTime}
                    onChange={(e) => {
                      setScheduledTime(e.target.value)
                      handleDateTimeChange(scheduledDate, e.target.value)
                    }}
                    className="mt-2"
                  />
                </div>
              </div>
              {errors.scheduledAt && <p className="text-sm text-red-600">{errors.scheduledAt}</p>}

              {/* Recipients */}
              <div>
                <div className="flex items-center justify-between mb-4">
                  <Label>Select Recipients</Label>
                  <div className="flex space-x-2">
                    <Button type="button" variant="outline" size="sm" onClick={selectAll}>
                      Select All
                    </Button>
                    <Button type="button" variant="outline" size="sm" onClick={deselectAll}>
                      Deselect All
                    </Button>
                  </div>
                </div>

                <div className="max-h-64 overflow-y-auto space-y-2 border rounded-lg p-4">
                  {users.map((user) => (
                    <div key={user.id} className="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded">
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
                {errors.recipients && <p className="text-sm text-red-600 mt-2">{errors.recipients}</p>}

                {selectedUsers.length > 0 && (
                  <div className="border-t pt-4 mt-4">
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
              </div>

              {/* Schedule Button */}
              <div className="flex justify-end">
                <Button
                  type="submit"
                  disabled={
                    processing || !data.content.trim() || selectedUsers.length === 0 ||
                    !scheduledDate || !scheduledTime
                  }
                  className="flex items-center space-x-2"
                >
                  {processing ? <Loader2 className="h-4 w-4 animate-spin" /> : <Clock className="h-4 w-4" />}
                  <span>Schedule Message</span>
                </Button>
              </div>
            </CardContent>
          </Card>
        </form>
      </div>
    </AppLayout>
  )
}
