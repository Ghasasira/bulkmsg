import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { UserPlus, ArrowLeft, Loader2 } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import { BreadcrumbItem } from "@/types"
import { Head, useForm, Link } from "@inertiajs/react"

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
  },
  {
    title: 'Contacts',
    href: '/contacts',
  },
  {
    title: 'Create User',
    href: '/contacts/create',
  },
];

export default function CreateContact() {
  const { data, setData, post, processing, errors, reset } = useForm({
    name: '',
    phone: '',
    email: '',
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()

    post('/contacts', {
      onSuccess: () => {
        reset()
      },
    })
  }

//   const formatPhoneNumber = (value: string) => {
//     // Remove all non-numeric characters
//     const cleaned = value.replace(/\D/g, '')

//     // Format as +256 XXX XXX XXX for Uganda numbers
//     if (cleaned.length <= 3) {
//       return cleaned
//     } else if (cleaned.length <= 6) {
//       return `${cleaned.slice(0, 3)} ${cleaned.slice(3)}`
//     } else if (cleaned.length <= 9) {
//       return `${cleaned.slice(0, 3)} ${cleaned.slice(3, 6)} ${cleaned.slice(6)}`
//     } else {
//       return `${cleaned.slice(0, 3)} ${cleaned.slice(3, 6)} ${cleaned.slice(6, 9)} ${cleaned.slice(9, 12)}`
//     }
//   }

  const handlePhoneChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    let value = e.target.value

    // If user starts typing and doesn't include country code, add it
    if (value && !value.startsWith('+') && !value.startsWith('256') && !value.startsWith('0')) {
      value = '+256' + value
    }

    setData('phone', value)
  }

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Create User" />
      <div className="px-4 w-full mx-auto space-y-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <UserPlus className="h-6 w-6" />
            <h1 className="text-2xl font-bold">Create New User</h1>
          </div>
          <Link href="/users">
            <Button variant="outline" className="flex items-center space-x-2">
              <ArrowLeft className="h-4 w-4" />
              <span>Back to Users</span>
            </Button>
          </Link>
        </div>

        <div className="max-w-2xl mx-auto">
          <Card>
            <CardHeader>
              <CardTitle>User Information</CardTitle>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSubmit} className="space-y-6">
                {/* Name Field */}
                <div className="space-y-2">
                  <Label htmlFor="name">
                    Full Name <span className="text-red-500">*</span>
                  </Label>
                  <Input
                    id="name"
                    type="text"
                    placeholder="Enter full name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    className={errors.name ? 'border-red-500' : ''}
                  />
                  {errors.name && (
                    <p className="text-sm text-red-600">{errors.name}</p>
                  )}
                </div>

                {/* Phone Field */}
                <div className="space-y-2">
                  <Label htmlFor="phone">
                    Phone Number <span className="text-red-500">*</span>
                  </Label>
                  <Input
                    id="phone"
                    type="tel"
                    placeholder="+256 XXX XXX XXX"
                    value={data.phone}
                    onChange={handlePhoneChange}
                    className={errors.phone ? 'border-red-500' : ''}
                  />
                  <p className="text-sm text-gray-500">
                    Include country code (e.g., +256 for Uganda)
                  </p>
                  {errors.phone && (
                    <p className="text-sm text-red-600">{errors.phone}</p>
                  )}
                </div>

                {/* Email Field */}
                <div className="space-y-2">
                  <Label htmlFor="email">Email Address</Label>
                  <Input
                    id="email"
                    type="email"
                    placeholder="user@example.com"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    className={errors.email ? 'border-red-500' : ''}
                  />
                  <p className="text-sm text-gray-500">Optional</p>
                  {errors.email && (
                    <p className="text-sm text-red-600">{errors.email}</p>
                  )}
                </div>

                {/* Submit Button */}
                <div className="flex items-center justify-end space-x-4 pt-4 border-t">
                  <Link href="/contacts">
                    <Button type="button" variant="outline">
                      Cancel
                    </Button>
                  </Link>
                  <Button
                    type="submit"
                    disabled={processing || !data.name.trim() || !data.phone.trim()}
                    className="flex items-center space-x-2"
                  >
                    {processing ? (
                      <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                      <UserPlus className="h-4 w-4" />
                    )}
                    <span>{processing ? 'Creating...' : 'Create User'}</span>
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>

          {/* Help Card */}
          <Card className="mt-6">
            <CardHeader>
              <CardTitle className="text-lg">Tips for Adding Users</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3 text-sm">
              <div className="flex items-start space-x-2">
                <span className="font-semibold text-blue-600">📱</span>
                <div>
                  <p className="font-medium">Phone Number Format</p>
                  <p className="text-gray-600">
                    Always include the country code. For Uganda: +256701234567
                  </p>
                </div>
              </div>

              <div className="flex items-start space-x-2">
                <span className="font-semibold text-green-600">✅</span>
                <div>
                  <p className="font-medium">Duplicate Check</p>
                  <p className="text-gray-600">
                    Phone numbers and emails must be unique in the system
                  </p>
                </div>
              </div>

              <div className="flex items-start space-x-2">
                <span className="font-semibold text-purple-600">📧</span>
                <div>
                  <p className="font-medium">Email Optional</p>
                  <p className="text-gray-600">
                    Email is optional but recommended for better contact management
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  )
}
