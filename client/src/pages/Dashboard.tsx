import React from "react";
import { PageTransition, Card } from "@/components/ui/core";
import { useProducts } from "@/hooks/use-products";
import { useBrands } from "@/hooks/use-brands";
import { useCategories } from "@/hooks/use-categories";
import { Package, Tag, Layers, TrendingUp, ArrowUpRight } from "lucide-react";
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { formatCurrency } from "@/lib/utils";

// Mock data since we don't have historical metrics in the API
const chartData = [
  { name: 'Mon', revenue: 4000, products: 24 },
  { name: 'Tue', revenue: 3000, products: 13 },
  { name: 'Wed', revenue: 5000, products: 38 },
  { name: 'Thu', revenue: 2780, products: 39 },
  { name: 'Fri', revenue: 8900, products: 48 },
  { name: 'Sat', revenue: 2390, products: 38 },
  { name: 'Sun', revenue: 3490, products: 43 },
];

function StatCard({ title, value, icon: Icon, loading, trend }: any) {
  return (
    <Card className="p-6 relative overflow-hidden group hover:shadow-md transition-shadow">
      <div className="absolute -right-6 -top-6 w-24 h-24 bg-indigo-500/5 rounded-full blur-2xl group-hover:bg-indigo-500/10 transition-colors"></div>
      <div className="flex items-start justify-between">
        <div>
          <p className="text-sm font-medium text-slate-500 dark:text-slate-400 mb-1">{title}</p>
          {loading ? (
            <div className="h-8 w-24 bg-slate-200 dark:bg-slate-800 rounded animate-pulse"></div>
          ) : (
            <h3 className="text-3xl font-display font-bold text-slate-900 dark:text-white">{value}</h3>
          )}
        </div>
        <div className="p-3 bg-indigo-50 dark:bg-indigo-500/10 rounded-2xl text-indigo-600 dark:text-indigo-400">
          <Icon className="w-6 h-6" />
        </div>
      </div>
      <div className="mt-4 flex items-center text-sm">
        <span className="text-emerald-500 font-medium flex items-center"><ArrowUpRight className="w-4 h-4 mr-1"/> {trend}</span>
        <span className="text-slate-400 ml-2">vs last month</span>
      </div>
    </Card>
  );
}

export default function Dashboard() {
  const { data: products, isLoading: loadingProducts } = useProducts();
  const { data: brands, isLoading: loadingBrands } = useBrands();
  const { data: categories, isLoading: loadingCategories } = useCategories();

  return (
    <PageTransition className="space-y-8">
      <div>
        <h1 className="text-3xl font-display font-bold tracking-tight text-slate-900 dark:text-white">Dashboard Overview</h1>
        <p className="text-slate-500 mt-2">Welcome back! Here's what's happening with your store today.</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <StatCard 
          title="Total Products" 
          value={products?.length || 0} 
          loading={loadingProducts} 
          icon={Package} 
          trend="+12.5%" 
        />
        <StatCard 
          title="Active Brands" 
          value={brands?.length || 0} 
          loading={loadingBrands} 
          icon={Tag} 
          trend="+4.2%" 
        />
        <StatCard 
          title="Categories" 
          value={categories?.length || 0} 
          loading={loadingCategories} 
          icon={Layers} 
          trend="+2.1%" 
        />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card className="p-6 col-span-1 lg:col-span-2">
          <div className="flex items-center justify-between mb-6">
            <div>
              <h3 className="text-lg font-bold font-display">Revenue Analytics</h3>
              <p className="text-sm text-slate-500">Weekly sales performance</p>
            </div>
            <div className="p-2 bg-slate-100 dark:bg-slate-800 rounded-lg">
              <TrendingUp className="w-5 h-5 text-slate-600 dark:text-slate-300" />
            </div>
          </div>
          <div className="h-[300px] w-full">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={chartData} margin={{ top: 10, right: 0, left: 0, bottom: 0 }}>
                <defs>
                  <linearGradient id="colorRevenue" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#6366f1" stopOpacity={0.3}/>
                    <stop offset="95%" stopColor="#6366f1" stopOpacity={0}/>
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#334155" opacity={0.2} />
                <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} dy={10} />
                <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: '#64748b' }} tickFormatter={(val) => `$${val/1000}k`} />
                <Tooltip 
                  contentStyle={{ borderRadius: '12px', border: 'none', boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.1)' }}
                  formatter={(value: number) => [formatCurrency(value), 'Revenue']}
                />
                <Area type="monotone" dataKey="revenue" stroke="#6366f1" strokeWidth={3} fillOpacity={1} fill="url(#colorRevenue)" />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </Card>

        <Card className="p-0 overflow-hidden flex flex-col">
          <div className="p-6 border-b border-border">
            <h3 className="text-lg font-bold font-display">Recent Products</h3>
            <p className="text-sm text-slate-500">Latest additions to inventory</p>
          </div>
          <div className="flex-1 overflow-auto p-2">
            {loadingProducts ? (
              <div className="p-4 space-y-4">
                {[1,2,3,4].map(i => (
                  <div key={i} className="flex gap-4 items-center animate-pulse">
                    <div className="w-12 h-12 bg-slate-200 dark:bg-slate-800 rounded-lg" />
                    <div className="flex-1 space-y-2"><div className="h-4 bg-slate-200 dark:bg-slate-800 rounded w-3/4"></div><div className="h-3 bg-slate-200 dark:bg-slate-800 rounded w-1/2"></div></div>
                  </div>
                ))}
              </div>
            ) : products && products.length > 0 ? (
              <div className="divide-y divide-border">
                {products.slice(0, 5).map(product => (
                  <div key={product.id} className="p-4 flex items-center gap-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    <div className="w-12 h-12 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center shrink-0 overflow-hidden">
                      {product.image ? (
                        <img src={product.image} alt={product.name} className="w-full h-full object-cover" />
                      ) : (
                        <Package className="w-6 h-6 text-indigo-500" />
                      )}
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="font-semibold text-sm text-slate-900 dark:text-white truncate">{product.name}</p>
                      <p className="text-xs text-slate-500 truncate">{product.itemCode}</p>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="p-8 text-center text-slate-500">No products found.</div>
            )}
          </div>
        </Card>
      </div>
    </PageTransition>
  );
}
