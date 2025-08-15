import { useState } from "react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Upload, Search } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import { BreadcrumbItem } from "@/types"
import { Head, usePage, router } from "@inertiajs/react"

interface Customer {
  id: number
  name: string
  local_amt: string
  no_due_days: number
  number1: string
  number2: string
  created_at: string
}

type PageProps = {
  customers: Customer[]
  filters: {
    search?: string
  }
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Customers', href: '/customers' },
];

export default function CustomersPage() {
  const { customers, filters } = usePage<PageProps>().props
  const [searchTerm, setSearchTerm] = useState(filters.search || "")
  const [file, setFile] = useState<File | null>(null)

  const handleSearch = (value: string) => {
    setSearchTerm(value)
    setTimeout(() => {
      router.get('/contacts', { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
      })
    }, 300)
  }

  const handleUpload = () => {
    if (!file) return
    const formData = new FormData()
    formData.append("file", file)
    router.post(route("customers.import"), formData, {
      onSuccess: () => {
        setFile(null)
      }
    })
  }

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Customers" />
      <div className="w-full p-4 mx-auto space-y-6">

        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <h1 className="text-2xl font-bold">Customers</h1>
          </div>
          <div className="flex space-x-2">
            <Input
              type="file"
              accept=".xlsx,.xls"
              onChange={(e) => setFile(e.target.files?.[0] || null)}
            />
            <Button onClick={handleUpload} disabled={!file}>
              <Upload className="h-4 w-4 mr-2" /> Upload Excel
            </Button>
          </div>
        </div>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center justify-between">
              <span>All Customers ({customers.length})</span>
              <div className="relative w-64">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                <Input
                  placeholder="Search customers..."
                  value={searchTerm}
                  onChange={(e) => handleSearch(e.target.value)}
                  className="pl-10"
                />
              </div>
            </CardTitle>
          </CardHeader>
          <CardContent>
            {customers.length > 0 ? (
              <div className="overflow-x-auto">
                <table className="w-full border">
                  <thead className="bg-gray-100">
                    <tr>
                      <th className="p-2 border">Name</th>
                      <th className="p-2 border">Loan Amount</th>
                      <th className="p-2 border">No. Due Days</th>
                      <th className="p-2 border">Number 1</th>
                      <th className="p-2 border">Number 2</th>
                    </tr>
                  </thead>
                  <tbody>
                    {customers.map((c) => (
                      <tr key={c.id} className="hover:bg-gray-50">
                        <td className="p-2 border">{c.name}</td>
                        <td className="p-2 border">{c.local_amt}</td>
                        <td className="p-2 border">{c.no_due_days}</td>
                        <td className="p-2 border">{c.number1}</td>
                        <td className="p-2 border">{c.number2}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <p className="text-center text-gray-500 py-8">No customers found</p>
            )}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  )
}
