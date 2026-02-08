/**
 * Settings Page - Application settings and configuration
 */
import React from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Settings as SettingsIcon, Globe, Bell, Shield, Database, Palette } from 'lucide-react';
import { toast } from 'sonner';

export default function Settings() {
  return (
    <DashboardLayout title="Settings" subtitle="Configure your KeyLMS application">
      <div className="space-y-4 animate-fade-in-up max-w-4xl">
        <Tabs defaultValue="general" className="space-y-4">
          <TabsList className="bg-[#f1f5f9] p-1">
            <TabsTrigger value="general" className="text-xs data-[state=active]:bg-white"><SettingsIcon className="w-3.5 h-3.5 mr-1.5" /> General</TabsTrigger>
            <TabsTrigger value="notifications" className="text-xs data-[state=active]:bg-white"><Bell className="w-3.5 h-3.5 mr-1.5" /> Notifications</TabsTrigger>
            <TabsTrigger value="security" className="text-xs data-[state=active]:bg-white"><Shield className="w-3.5 h-3.5 mr-1.5" /> Security</TabsTrigger>
            <TabsTrigger value="integrations" className="text-xs data-[state=active]:bg-white"><Database className="w-3.5 h-3.5 mr-1.5" /> Integrations</TabsTrigger>
          </TabsList>

          <TabsContent value="general">
            <Card className="p-6 border-[#e2e8f0]/50 shadow-card space-y-6">
              <div>
                <h3 className="font-heading font-semibold text-[#1e293b] mb-1">Organisation Details</h3>
                <p className="text-sm text-[#94a3b8] mb-4">Manage your organisation's basic information</p>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <Label className="text-sm text-[#1e293b] font-medium">Organisation Name</Label>
                    <Input defaultValue="Key Company" className="mt-1.5 border-[#e2e8f0]" />
                  </div>
                  <div>
                    <Label className="text-sm text-[#1e293b] font-medium">RTO Code</Label>
                    <Input defaultValue="40001" className="mt-1.5 border-[#e2e8f0]" />
                  </div>
                  <div>
                    <Label className="text-sm text-[#1e293b] font-medium">Contact Email</Label>
                    <Input defaultValue="admin@keycompany.com" className="mt-1.5 border-[#e2e8f0]" />
                  </div>
                  <div>
                    <Label className="text-sm text-[#1e293b] font-medium">Phone</Label>
                    <Input defaultValue="+61 2 9000 0000" className="mt-1.5 border-[#e2e8f0]" />
                  </div>
                </div>
              </div>
              <Separator />
              <div>
                <h3 className="font-heading font-semibold text-[#1e293b] mb-1">Appearance</h3>
                <p className="text-sm text-[#94a3b8] mb-4">Customize the look and feel of your LMS</p>
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-medium text-[#1e293b]">Dark Mode</p>
                      <p className="text-xs text-[#94a3b8]">Switch between light and dark themes</p>
                    </div>
                    <Switch />
                  </div>
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-medium text-[#1e293b]">Compact Sidebar</p>
                      <p className="text-xs text-[#94a3b8]">Use a collapsed sidebar by default</p>
                    </div>
                    <Switch />
                  </div>
                </div>
              </div>
              <div className="flex justify-end">
                <Button className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => toast.success('Settings saved')}>Save Changes</Button>
              </div>
            </Card>
          </TabsContent>

          <TabsContent value="notifications">
            <Card className="p-6 border-[#e2e8f0]/50 shadow-card space-y-4">
              <h3 className="font-heading font-semibold text-[#1e293b]">Notification Preferences</h3>
              <div className="space-y-4">
                {[
                  { title: 'New Enrolments', desc: 'Get notified when a new student enrols' },
                  { title: 'Assessment Submissions', desc: 'Get notified when an assessment is submitted' },
                  { title: 'Student At-Risk Alerts', desc: 'Get notified when a student is flagged as at-risk' },
                  { title: 'System Updates', desc: 'Get notified about system maintenance and updates' },
                ].map((item) => (
                  <div key={item.title} className="flex items-center justify-between py-2">
                    <div>
                      <p className="text-sm font-medium text-[#1e293b]">{item.title}</p>
                      <p className="text-xs text-[#94a3b8]">{item.desc}</p>
                    </div>
                    <Switch defaultChecked />
                  </div>
                ))}
              </div>
              <div className="flex justify-end pt-2">
                <Button className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => toast.success('Notification preferences saved')}>Save</Button>
              </div>
            </Card>
          </TabsContent>

          <TabsContent value="security">
            <Card className="p-6 border-[#e2e8f0]/50 shadow-card space-y-4">
              <h3 className="font-heading font-semibold text-[#1e293b]">Security Settings</h3>
              <p className="text-sm text-[#94a3b8]">Manage authentication and security policies</p>
              <div className="space-y-4">
                <div className="flex items-center justify-between py-2">
                  <div>
                    <p className="text-sm font-medium text-[#1e293b]">Two-Factor Authentication</p>
                    <p className="text-xs text-[#94a3b8]">Require 2FA for all admin users</p>
                  </div>
                  <Switch />
                </div>
                <div className="flex items-center justify-between py-2">
                  <div>
                    <p className="text-sm font-medium text-[#1e293b]">Session Timeout</p>
                    <p className="text-xs text-[#94a3b8]">Auto-logout after inactivity</p>
                  </div>
                  <Input defaultValue="30" className="w-20 text-center border-[#e2e8f0]" />
                </div>
                <div className="flex items-center justify-between py-2">
                  <div>
                    <p className="text-sm font-medium text-[#1e293b]">Password Policy</p>
                    <p className="text-xs text-[#94a3b8]">Enforce strong password requirements</p>
                  </div>
                  <Switch defaultChecked />
                </div>
              </div>
              <div className="flex justify-end pt-2">
                <Button className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => toast.success('Security settings saved')}>Save</Button>
              </div>
            </Card>
          </TabsContent>

          <TabsContent value="integrations">
            <Card className="p-6 border-[#e2e8f0]/50 shadow-card space-y-4">
              <h3 className="font-heading font-semibold text-[#1e293b]">Integrations</h3>
              <p className="text-sm text-[#94a3b8]">Connect KeyLMS with external services</p>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {[
                  { name: 'Supabase', desc: 'Database and authentication', status: 'Connected', color: 'text-[#22c55e]' },
                  { name: 'NytroAI', desc: 'AI-powered validation', status: 'Connected', color: 'text-[#22c55e]' },
                  { name: 'AVETMISS', desc: 'Reporting compliance', status: 'Configure', color: 'text-[#f59e0b]' },
                  { name: 'Azure AD', desc: 'Single sign-on', status: 'Configure', color: 'text-[#f59e0b]' },
                ].map((integration) => (
                  <div key={integration.name} className="p-4 rounded-lg border border-[#e2e8f0] hover:border-[#3b82f6] transition-colors">
                    <div className="flex items-center justify-between mb-2">
                      <h4 className="font-medium text-[#1e293b]">{integration.name}</h4>
                      <span className={`text-xs font-medium ${integration.color}`}>{integration.status}</span>
                    </div>
                    <p className="text-xs text-[#94a3b8]">{integration.desc}</p>
                  </div>
                ))}
              </div>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </DashboardLayout>
  );
}
