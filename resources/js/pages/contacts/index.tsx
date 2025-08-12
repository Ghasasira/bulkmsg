import { useState } from "react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Users, Search, UserPlus, Mail, Phone, Calendar } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import { BreadcrumbItem } from "@/types"
import { Head, usePage, router, Link } from "@inertiajs/react"

interface User {
  id: number
  name: string
  phone: string
  email: string
  created_at: string
}

type PageProps = {
  users: User[]
  filters: {
    search?: string
  }
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
  },
  {
    title: 'Contacts',
    href: '/contacts',
  },
];

export default function UsersPage() {
  const { users, filters } = usePage<PageProps>().props
  const [searchTerm, setSearchTerm] = useState(filters.search || "")

  const handleSearch = (value: string) => {
    setSearchTerm(value)

    // Debounce search
    setTimeout(() => {
      router.get('/users', { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
      })
    }, 300)
  }

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Contacts" />
      <div className="w-full px-4 mx-auto space-y-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <Users className="h-6 w-6" />
            <h1 className="text-2xl font-bold">Users</h1>
          </div>
          <Link href="/contacts/create">
            <Button className="flex items-center space-x-2">
              <UserPlus className="h-4 w-4" />
              <span>Add New User</span>
            </Button>
          </Link>
        </div>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center justify-between">
              <span>All Users ({users.length})</span>
              <div className="relative w-64">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                <Input
                  placeholder="Search users..."
                  value={searchTerm}
                  onChange={(e) => handleSearch(e.target.value)}
                  className="pl-10"
                />
              </div>
            </CardTitle>
          </CardHeader>
          <CardContent>
            {users.length > 0 ? (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {users.map((user) => (
                  <div key={user.id} className="p-4 border rounded-lg hover:shadow-md transition-shadow group">
                    <div className="flex items-start justify-between">
                      <div className="flex-1 min-w-0">
                        <h3 className="font-medium text-lg truncate">{user.name}</h3>

                        <div className="flex items-center space-x-1 mt-2">
                          <Phone className="h-3 w-3 text-gray-400" />
                          <p className="text-sm text-gray-600 truncate">{user.phone}</p>
                        </div>

                        {user.email && (
                          <div className="flex items-center space-x-1 mt-1">
                            <Mail className="h-3 w-3 text-gray-400" />
                            <p className="text-sm text-gray-600 truncate">{user.email}</p>
                          </div>
                        )}

                        <div className="flex items-center space-x-1 mt-2">
                          <Calendar className="h-3 w-3 text-gray-400" />
                          <p className="text-xs text-gray-400">
                            Joined: {new Date(user.created_at).toLocaleDateString()}
                          </p>
                        </div>
                      </div>

                      <div className="flex flex-col items-end space-y-2">
                        <Badge variant="outline" className="text-xs">
                          ID: {user.id}
                        </Badge>

                        {/* Action buttons - show on hover */}
                        <div className="opacity-0 group-hover:opacity-100 transition-opacity flex space-x-1">
                          <Link href={`/users/${user.id}`}>
                            <Button variant="ghost" size="sm" className="h-8 px-2 text-xs">
                              View
                            </Button>
                          </Link>
                          <Link href={`/users/${user.id}/edit`}>
                            <Button variant="ghost" size="sm" className="h-8 px-2 text-xs">
                              Edit
                            </Button>
                          </Link>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-12">
                <Users className="mx-auto h-12 w-12 text-gray-400 mb-4" />
                <h3 className="text-lg font-medium text-gray-900 mb-2">No users found</h3>
                <p className="text-gray-500 mb-6">
                  {searchTerm
                    ? "No users match your search criteria. Try adjusting your search terms."
                    : "Get started by adding your first user to the system."
                  }
                </p>
                {!searchTerm && (
                  <Link href="/contacts/create">
                    <Button className="flex items-center space-x-2">
                      <UserPlus className="h-4 w-4" />
                      <span>Add Your First User</span>
                    </Button>
                  </Link>
                )}
              </div>
            )}
          </CardContent>
        </Card>

        {/* Quick Stats Card */}
        {users.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Quick Stats</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="text-center p-4 bg-blue-50 rounded-lg">
                  <div className="text-2xl font-bold text-blue-600">{users.length}</div>
                  <div className="text-sm text-blue-600">Total Users</div>
                </div>
                <div className="text-center p-4 bg-green-50 rounded-lg">
                  <div className="text-2xl font-bold text-green-600">
                    {users.filter(user => user.email).length}
                  </div>
                  <div className="text-sm text-green-600">With Email</div>
                </div>
                <div className="text-center p-4 bg-purple-50 rounded-lg">
                  <div className="text-2xl font-bold text-purple-600">
                    {users.filter(user => user.created_at &&
                      new Date(user.created_at) > new Date(Date.now() - 30 * 24 * 60 * 60 * 1000)
                    ).length}
                  </div>
                  <div className="text-sm text-purple-600">Added This Month</div>
                </div>
              </div>
            </CardContent>
          </Card>
        )}
      </div>
    </AppLayout>
  )
}
