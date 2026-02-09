/**
 * Settings Page — Application settings and configuration
 * Connected to Supabase: settings table
 */
import React, { useState, useEffect } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Settings as SettingsIcon, Bell, Shield, Database, Loader2, AlertCircle, Save, Menu, Image, Plus, Trash2, GripVertical, ExternalLink } from 'lucide-react';
import { toast } from 'sonner';
import { useSupabaseQuery } from '@/hooks/useSupabaseQuery';
import { fetchSettings, updateSetting } from '@/lib/api';

interface MenuItem {
  title: string;
  link: string;
  target?: string;
}

interface FeaturedImage {
  image: string;
  link: string;
}

export default function Settings() {
  const { data: settingsData, loading, error, refetch } = useSupabaseQuery(
    () => fetchSettings(),
    []
  );

  const [orgName, setOrgName] = useState('');
  const [rtoCode, setRtoCode] = useState('');
  const [contactEmail, setContactEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [saving, setSaving] = useState(false);

  // Menu settings state
  const [sidebarItems, setSidebarItems] = useState<MenuItem[]>([]);
  const [footerItems, setFooterItems] = useState<MenuItem[]>([]);
  const [savingMenu, setSavingMenu] = useState(false);

  // Featured images state
  const [featuredImages, setFeaturedImages] = useState<FeaturedImage[]>([]);
  const [savingImages, setSavingImages] = useState(false);

  // Populate form when settings load
  useEffect(() => {
    if (settingsData) {
      setOrgName(settingsData['organisation_name'] ?? settingsData['site_name'] ?? '');
      setRtoCode(settingsData['rto_code'] ?? '');
      setContactEmail(settingsData['contact_email'] ?? settingsData['admin_email'] ?? '');
      setPhone(settingsData['phone'] ?? '');

      // Parse menu settings
      try {
        const sidebar = settingsData['sidebar'];
        if (sidebar) setSidebarItems(typeof sidebar === 'string' ? JSON.parse(sidebar) : sidebar);
      } catch { setSidebarItems([]); }
      try {
        const footer = settingsData['footer'];
        if (footer) setFooterItems(typeof footer === 'string' ? JSON.parse(footer) : footer);
      } catch { setFooterItems([]); }

      // Parse featured images
      try {
        const fi = settingsData['featured_images'];
        if (fi) setFeaturedImages(typeof fi === 'string' ? JSON.parse(fi) : fi);
      } catch { setFeaturedImages([]); }
    }
  }, [settingsData]);

  const handleSaveMenu = async () => {
    setSavingMenu(true);
    try {
      const cleanSidebar = sidebarItems.filter(i => i.title || i.link);
      const cleanFooter = footerItems.filter(i => i.title || i.link);
      await Promise.all([
        updateSetting('sidebar', JSON.stringify(cleanSidebar)),
        updateSetting('footer', JSON.stringify(cleanFooter)),
      ]);
      toast.success('Menu settings saved successfully');
      refetch();
    } catch {
      toast.error('Failed to save menu settings');
    } finally {
      setSavingMenu(false);
    }
  };

  const handleSaveFeaturedImages = async () => {
    setSavingImages(true);
    try {
      const clean = featuredImages.filter(i => i.image && i.link);
      await updateSetting('featured_images', JSON.stringify(clean));
      toast.success('Featured images saved successfully');
      refetch();
    } catch {
      toast.error('Failed to save featured images');
    } finally {
      setSavingImages(false);
    }
  };

  const updateMenuItem = (list: MenuItem[], setList: React.Dispatch<React.SetStateAction<MenuItem[]>>, index: number, field: keyof MenuItem, value: string) => {
    setList(list.map((item, i) => i === index ? { ...item, [field]: value } : item));
  };

  const handleSaveGeneral = async () => {
    setSaving(true);
    try {
      await Promise.all([
        updateSetting('organisation_name', orgName),
        updateSetting('rto_code', rtoCode),
        updateSetting('contact_email', contactEmail),
        updateSetting('phone', phone),
      ]);
      toast.success('Settings saved successfully');
      refetch();
    } catch (e) {
      toast.error('Failed to save settings');
    } finally {
      setSaving(false);
    }
  };

  return (
    <DashboardLayout title="Settings" subtitle="Configure your NytroLMS application">
      <div className="space-y-4 animate-fade-in-up max-w-4xl">
        {error && (
          <Card className="p-6 border-red-200 bg-red-50">
            <div className="flex items-center gap-3 text-red-700">
              <AlertCircle className="w-5 h-5" />
              <p className="text-sm">{error}</p>
              <Button variant="outline" size="sm" onClick={refetch}>Retry</Button>
            </div>
          </Card>
        )}

        {loading ? (
          <Card className="p-12 flex items-center justify-center border-[#e2e8f0]/50 shadow-card">
            <Loader2 className="w-6 h-6 animate-spin text-[#3b82f6]" />
            <span className="ml-3 text-sm text-[#64748b]">Loading settings...</span>
          </Card>
        ) : (
          <Tabs defaultValue="general" className="space-y-4">
            <TabsList className="bg-[#f1f5f9] p-1 border border-[#e2e8f0] flex-wrap">
              <TabsTrigger value="general" className="text-xs data-[state=active]:bg-white"><SettingsIcon className="w-3.5 h-3.5 mr-1.5" /> General</TabsTrigger>
              <TabsTrigger value="menu" className="text-xs data-[state=active]:bg-white"><Menu className="w-3.5 h-3.5 mr-1.5" /> Menu</TabsTrigger>
              <TabsTrigger value="featured-images" className="text-xs data-[state=active]:bg-white"><Image className="w-3.5 h-3.5 mr-1.5" /> Featured Images</TabsTrigger>
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
                      <Input value={orgName} onChange={(e) => setOrgName(e.target.value)} className="mt-1.5 border-[#e2e8f0]" />
                    </div>
                    <div>
                      <Label className="text-sm text-[#1e293b] font-medium">RTO Code</Label>
                      <Input value={rtoCode} onChange={(e) => setRtoCode(e.target.value)} className="mt-1.5 border-[#e2e8f0]" />
                    </div>
                    <div>
                      <Label className="text-sm text-[#1e293b] font-medium">Contact Email</Label>
                      <Input value={contactEmail} onChange={(e) => setContactEmail(e.target.value)} className="mt-1.5 border-[#e2e8f0]" />
                    </div>
                    <div>
                      <Label className="text-sm text-[#1e293b] font-medium">Phone</Label>
                      <Input value={phone} onChange={(e) => setPhone(e.target.value)} className="mt-1.5 border-[#e2e8f0]" />
                    </div>
                  </div>
                </div>

                <Separator />

                {/* Display all settings from DB */}
                {settingsData && Object.keys(settingsData).length > 0 && (
                  <div>
                    <h3 className="font-heading font-semibold text-[#1e293b] mb-1">All Settings</h3>
                    <p className="text-sm text-[#94a3b8] mb-4">Current configuration values from the database</p>
                    <div className="space-y-2">
                      {Object.entries(settingsData).map(([key, value]) => (
                        <div key={key} className="flex items-center justify-between py-2 px-3 rounded-lg bg-[#f8fafc] border border-[#e2e8f0]">
                          <span className="text-xs font-mono text-[#64748b]">{key}</span>
                          <span className="text-xs text-[#1e293b] font-medium max-w-[50%] truncate">{value || '—'}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

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
                  <Button
                    className="bg-[#3b82f6] hover:bg-[#2563eb] text-white"
                    onClick={handleSaveGeneral}
                    disabled={saving}
                  >
                    {saving ? <Loader2 className="w-4 h-4 mr-1.5 animate-spin" /> : <Save className="w-4 h-4 mr-1.5" />}
                    {saving ? 'Saving...' : 'Save Changes'}
                  </Button>
                </div>
              </Card>
            </TabsContent>

            {/* ── Menu Settings Tab ── */}
            <TabsContent value="menu">
              <Card className="p-6 border-[#e2e8f0]/50 shadow-card space-y-6">
                {/* Sidebar Menu */}
                <div>
                  <h3 className="font-heading font-semibold text-[#1e293b] mb-1">Sidebar Menu</h3>
                  <p className="text-sm text-[#94a3b8] mb-4">Configure the sidebar navigation links</p>
                  <div className="space-y-2">
                    {sidebarItems.map((item, idx) => (
                      <div key={idx} className="flex items-center gap-2 p-2 rounded-lg border border-[#e2e8f0] bg-[#f8fafc]">
                        <GripVertical className="w-4 h-4 text-[#94a3b8] flex-shrink-0" />
                        <Input
                          placeholder="Title"
                          value={item.title}
                          onChange={(e) => updateMenuItem(sidebarItems, setSidebarItems, idx, 'title', e.target.value)}
                          className="flex-1 h-8 text-sm border-[#e2e8f0]"
                        />
                        <Input
                          placeholder="URL"
                          value={item.link}
                          onChange={(e) => updateMenuItem(sidebarItems, setSidebarItems, idx, 'link', e.target.value)}
                          className="flex-1 h-8 text-sm border-[#e2e8f0]"
                        />
                        <select
                          className="h-8 rounded-md border border-[#e2e8f0] px-2 text-xs bg-white"
                          value={item.target || ''}
                          onChange={(e) => updateMenuItem(sidebarItems, setSidebarItems, idx, 'target', e.target.value)}
                        >
                          <option value="">Same tab</option>
                          <option value="_blank">New tab</option>
                        </select>
                        <Button variant="ghost" size="sm" className="h-8 w-8 p-0 text-[#94a3b8] hover:text-red-500" onClick={() => setSidebarItems(sidebarItems.filter((_, i) => i !== idx))}>
                          <Trash2 className="w-3.5 h-3.5" />
                        </Button>
                      </div>
                    ))}
                    <Button variant="outline" size="sm" onClick={() => setSidebarItems([...sidebarItems, { title: '', link: '', target: '' }])}>
                      <Plus className="w-3.5 h-3.5 mr-1" /> Add Sidebar Item
                    </Button>
                  </div>
                </div>

                <Separator />

                {/* Footer Menu */}
                <div>
                  <h3 className="font-heading font-semibold text-[#1e293b] mb-1">Footer Menu</h3>
                  <p className="text-sm text-[#94a3b8] mb-4">Configure the footer navigation links</p>
                  <div className="space-y-2">
                    {footerItems.map((item, idx) => (
                      <div key={idx} className="flex items-center gap-2 p-2 rounded-lg border border-[#e2e8f0] bg-[#f8fafc]">
                        <GripVertical className="w-4 h-4 text-[#94a3b8] flex-shrink-0" />
                        <Input
                          placeholder="Title"
                          value={item.title}
                          onChange={(e) => updateMenuItem(footerItems, setFooterItems, idx, 'title', e.target.value)}
                          className="flex-1 h-8 text-sm border-[#e2e8f0]"
                        />
                        <Input
                          placeholder="URL"
                          value={item.link}
                          onChange={(e) => updateMenuItem(footerItems, setFooterItems, idx, 'link', e.target.value)}
                          className="flex-1 h-8 text-sm border-[#e2e8f0]"
                        />
                        <select
                          className="h-8 rounded-md border border-[#e2e8f0] px-2 text-xs bg-white"
                          value={item.target || ''}
                          onChange={(e) => updateMenuItem(footerItems, setFooterItems, idx, 'target', e.target.value)}
                        >
                          <option value="">Same tab</option>
                          <option value="_blank">New tab</option>
                        </select>
                        <Button variant="ghost" size="sm" className="h-8 w-8 p-0 text-[#94a3b8] hover:text-red-500" onClick={() => setFooterItems(footerItems.filter((_, i) => i !== idx))}>
                          <Trash2 className="w-3.5 h-3.5" />
                        </Button>
                      </div>
                    ))}
                    <Button variant="outline" size="sm" onClick={() => setFooterItems([...footerItems, { title: '', link: '', target: '' }])}>
                      <Plus className="w-3.5 h-3.5 mr-1" /> Add Footer Item
                    </Button>
                  </div>
                </div>

                <div className="flex justify-end">
                  <Button className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={handleSaveMenu} disabled={savingMenu}>
                    {savingMenu ? <Loader2 className="w-4 h-4 mr-1.5 animate-spin" /> : <Save className="w-4 h-4 mr-1.5" />}
                    {savingMenu ? 'Saving...' : 'Save Menu Settings'}
                  </Button>
                </div>
              </Card>
            </TabsContent>

            {/* ── Featured Images Tab ── */}
            <TabsContent value="featured-images">
              <Card className="p-6 border-[#e2e8f0]/50 shadow-card space-y-6">
                <div>
                  <h3 className="font-heading font-semibold text-[#1e293b] mb-1">Featured Images</h3>
                  <p className="text-sm text-[#94a3b8] mb-4">Manage featured images displayed on the student dashboard. Each image requires a URL and a link destination.</p>
                  <div className="space-y-3">
                    {featuredImages.map((item, idx) => (
                      <div key={idx} className="flex items-start gap-3 p-3 rounded-lg border border-[#e2e8f0] bg-[#f8fafc]">
                        <div className="flex-1 space-y-2">
                          <div>
                            <Label className="text-xs text-[#64748b]">Image URL</Label>
                            <Input
                              placeholder="https://example.com/image.jpg"
                              value={item.image}
                              onChange={(e) => setFeaturedImages(featuredImages.map((fi, i) => i === idx ? { ...fi, image: e.target.value } : fi))}
                              className="h-8 text-sm border-[#e2e8f0]"
                            />
                          </div>
                          <div>
                            <Label className="text-xs text-[#64748b]">Link URL</Label>
                            <Input
                              placeholder="https://example.com/page"
                              value={item.link}
                              onChange={(e) => setFeaturedImages(featuredImages.map((fi, i) => i === idx ? { ...fi, link: e.target.value } : fi))}
                              className="h-8 text-sm border-[#e2e8f0]"
                            />
                          </div>
                        </div>
                        {item.image && (
                          <div className="w-24 h-16 rounded-md border border-[#e2e8f0] overflow-hidden flex-shrink-0 bg-white">
                            <img src={item.image} alt="Preview" className="w-full h-full object-cover" onError={(e) => { (e.target as HTMLImageElement).style.display = 'none'; }} />
                          </div>
                        )}
                        <div className="flex flex-col gap-1 flex-shrink-0">
                          {item.link && (
                            <a href={item.link} target="_blank" rel="noopener noreferrer" className="inline-flex items-center justify-center h-8 w-8 rounded-md text-[#94a3b8] hover:text-[#3b82f6] hover:bg-[#eff6ff]">
                              <ExternalLink className="w-3.5 h-3.5" />
                            </a>
                          )}
                          <Button variant="ghost" size="sm" className="h-8 w-8 p-0 text-[#94a3b8] hover:text-red-500" onClick={() => setFeaturedImages(featuredImages.filter((_, i) => i !== idx))}>
                            <Trash2 className="w-3.5 h-3.5" />
                          </Button>
                        </div>
                      </div>
                    ))}
                    <Button variant="outline" size="sm" onClick={() => setFeaturedImages([...featuredImages, { image: '', link: '' }])}>
                      <Plus className="w-3.5 h-3.5 mr-1" /> Add Featured Image
                    </Button>
                  </div>
                </div>

                <div className="flex justify-end">
                  <Button className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={handleSaveFeaturedImages} disabled={savingImages}>
                    {savingImages ? <Loader2 className="w-4 h-4 mr-1.5 animate-spin" /> : <Save className="w-4 h-4 mr-1.5" />}
                    {savingImages ? 'Saving...' : 'Save Featured Images'}
                  </Button>
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
                      <p className="text-sm font-medium text-[#1e293b]">Session Timeout (minutes)</p>
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
                <p className="text-sm text-[#94a3b8]">Connect NytroLMS with external services</p>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {[
                    { name: 'Supabase', desc: 'Database and authentication', status: 'Connected', color: 'text-[#22c55e]' },
                    { name: 'NytroAI', desc: 'AI-powered pre-marking and validation', status: 'Connected', color: 'text-[#22c55e]' },
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
        )}
      </div>
    </DashboardLayout>
  );
}
