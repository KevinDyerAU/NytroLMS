/**
 * Profile Page — User profile management
 * Edit personal info, upload avatar, change password
 * Connected to Supabase: users, user_details tables
 */
import { useState, useEffect } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { StatusBadge } from '@/components/StatusBadge';
import { useAuth } from '@/contexts/AuthContext';
import { useSupabaseQuery } from '@/hooks/useSupabaseQuery';
import {
  fetchProfile,
  updateProfile,
  updateProfileAvatar,
  changePassword,
  type ProfileData,
} from '@/lib/api';
import { supabase } from '@/lib/supabase';
import {
  User, Mail, Phone, MapPin, Globe, Clock, Briefcase,
  Camera, Lock, Save, Loader2, AlertCircle, Eye, EyeOff, UserX,
} from 'lucide-react';
import { toast } from 'sonner';

export default function Profile() {
  const { user: authUser } = useAuth();
  const [saving, setSaving] = useState(false);
  const [uploadingAvatar, setUploadingAvatar] = useState(false);
  const [avatarUrl, setAvatarUrl] = useState<string | null>(null);
  
  // Profile form state
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [username, setUsername] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');
  const [preferredName, setPreferredName] = useState('');
  const [language, setLanguage] = useState('');
  const [preferredLanguage, setPreferredLanguage] = useState('');
  const [timezone, setTimezone] = useState('');
  const [position, setPosition] = useState('');
  
  // Password change state
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showCurrentPassword, setShowCurrentPassword] = useState(false);
  const [showNewPassword, setShowNewPassword] = useState(false);
  const [changingPassword, setChangingPassword] = useState(false);

  const userId = authUser?.id;

  const { data: profile, loading, error, refetch } = useSupabaseQuery(
    () => userId ? fetchProfile(userId) : Promise.resolve(null),
    [userId]
  );

  // Populate form when profile loads
  useEffect(() => {
    if (profile) {
      setFirstName(profile.first_name ?? '');
      setLastName(profile.last_name ?? '');
      setUsername(profile.username ?? '');
      setPhone(profile.detail?.phone ?? '');
      setAddress(profile.detail?.address ?? '');
      setPreferredName(profile.detail?.preferred_name ?? '');
      setLanguage(profile.detail?.language ?? '');
      setPreferredLanguage(profile.detail?.preferred_language ?? '');
      setTimezone(profile.detail?.timezone ?? '');
      setPosition(profile.detail?.position ?? '');
      
      // Load avatar
      if (profile.detail?.avatar) {
        const { data } = supabase.storage.from('student-documents').getPublicUrl(profile.detail.avatar);
        setAvatarUrl(data.publicUrl);
      }
    }
  }, [profile]);

  const handleSaveProfile = async () => {
    if (!userId) return;
    setSaving(true);
    try {
      await updateProfile(userId, {
        first_name: firstName,
        last_name: lastName,
        username: username || undefined,
        phone: phone || undefined,
        address: address || undefined,
        preferred_name: preferredName || undefined,
        language: language || undefined,
        preferred_language: preferredLanguage || undefined,
        timezone: timezone || undefined,
        position: position || undefined,
      });
      toast.success('Profile updated successfully');
      refetch();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to update profile');
    } finally {
      setSaving(false);
    }
  };

  const handleAvatarUpload = async (file: File) => {
    if (!userId) return;
    setUploadingAvatar(true);
    try {
      const url = await updateProfileAvatar(userId, file);
      setAvatarUrl(url);
      toast.success('Avatar updated');
      refetch();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to upload avatar');
    } finally {
      setUploadingAvatar(false);
    }
  };

  const handleChangePassword = async () => {
    if (!newPassword || newPassword !== confirmPassword) {
      toast.error('Passwords do not match');
      return;
    }
    if (newPassword.length < 6) {
      toast.error('Password must be at least 6 characters');
      return;
    }
    
    setChangingPassword(true);
    try {
      await changePassword(newPassword);
      toast.success('Password changed successfully. Please log in again.');
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to change password');
    } finally {
      setChangingPassword(false);
    }
  };

  if (!authUser) {
    return (
      <DashboardLayout title="Profile" subtitle="Manage your account">
        <Card className="p-12 text-center">
          <p className="text-[#94a3b8]">Please log in to view your profile.</p>
        </Card>
      </DashboardLayout>
    );
  }

  return (
    <DashboardLayout title="Profile" subtitle="Manage your account settings">
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

        {/* Header Card with Avatar */}
        <Card className="border-[#e2e8f0]/50 shadow-card">
          <CardContent className="p-6">
            <div className="flex items-center gap-4">
              <div className="relative">
                <div className="w-20 h-20 rounded-full bg-gradient-nytro flex items-center justify-center overflow-hidden">
                  {avatarUrl ? (
                    <img src={avatarUrl} alt="Avatar" className="w-full h-full object-cover" />
                  ) : (
                    <span className="text-white text-2xl font-bold">
                      {authUser.name?.split(' ').map(n => n[0]).join('') || 'U'}
                    </span>
                  )}
                </div>
                <label className="absolute -bottom-1 -right-1 p-1.5 bg-[#3b82f6] text-white rounded-full cursor-pointer hover:bg-[#2563eb] transition-colors shadow-sm">
                  <input
                    type="file"
                    className="hidden"
                    accept="image/jpeg,image/png,image/gif,image/webp"
                    onChange={(e) => {
                      const file = e.target.files?.[0];
                      if (file) handleAvatarUpload(file);
                      e.target.value = '';
                    }}
                    disabled={uploadingAvatar}
                  />
                  {uploadingAvatar ? (
                    <Loader2 className="w-4 h-4 animate-spin" />
                  ) : (
                    <Camera className="w-4 h-4" />
                  )}
                </label>
              </div>
              <div>
                <h2 className="text-xl font-bold text-[#1e293b]">{authUser.name}</h2>
                <p className="text-sm text-[#64748b]">{authUser.email}</p>
                <div className="flex items-center gap-2 mt-1">
                  <StatusBadge status={profile?.role_name || authUser.role} />
                  <span className="text-xs text-[#94a3b8]">
                    {profile?.is_active === 1 ? 'Active' : 'Inactive'}
                  </span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Tabs defaultValue="general" className="space-y-4">
          <TabsList className="bg-[#f1f5f9] p-1 border border-[#e2e8f0]">
            <TabsTrigger value="general" className="text-xs data-[state=active]:bg-white">
              <User className="w-3.5 h-3.5 mr-1.5" /> General
            </TabsTrigger>
            <TabsTrigger value="contact" className="text-xs data-[state=active]:bg-white">
              <Phone className="w-3.5 h-3.5 mr-1.5" /> Contact
            </TabsTrigger>
            <TabsTrigger value="preferences" className="text-xs data-[state=active]:bg-white">
              <Globe className="w-3.5 h-3.5 mr-1.5" /> Preferences
            </TabsTrigger>
            <TabsTrigger value="security" className="text-xs data-[state=active]:bg-white">
              <Lock className="w-3.5 h-3.5 mr-1.5" /> Security
            </TabsTrigger>
          </TabsList>

          {/* General Tab */}
          <TabsContent value="general">
            <Card className="p-6 border-[#e2e8f0]/50 shadow-card space-y-6">
              <h3 className="font-heading font-semibold text-[#1e293b]">Personal Information</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <Label className="text-sm text-[#1e293b] font-medium">First Name</Label>
                  <Input
                    value={firstName}
                    onChange={(e) => setFirstName(e.target.value)}
                    className="mt-1.5 border-[#e2e8f0]"
                    disabled={loading}
                  />
                </div>
                <div>
                  <Label className="text-sm text-[#1e293b] font-medium">Last Name</Label>
                  <Input
                    value={lastName}
                    onChange={(e) => setLastName(e.target.value)}
                    className="mt-1.5 border-[#e2e8f0]"
                    disabled={loading}
                  />
                </div>
                <div>
                  <Label className="text-sm text-[#1e293b] font-medium">Preferred Name</Label>
                  <Input
                    value={preferredName}
                    onChange={(e) => setPreferredName(e.target.value)}
                    className="mt-1.5 border-[#e2e8f0]"
                    disabled={loading}
                    placeholder="How you'd like to be called"
                  />
                </div>
                <div>
                  <Label className="text-sm text-[#1e293b] font-medium">Username</Label>
                  <Input
                    value={username}
                    onChange={(e) => setUsername(e.target.value)}
                    className="mt-1.5 border-[#e2e8f0]"
                    disabled={loading}
                    placeholder="Optional username"
                  />
                </div>
              </div>
              <div className="flex justify-end">
                <Button
                  className="bg-[#3b82f6] hover:bg-[#2563eb] text-white"
                  onClick={handleSaveProfile}
                  disabled={saving || loading}
                >
                  {saving ? <Loader2 className="w-4 h-4 mr-1.5 animate-spin" /> : <Save className="w-4 h-4 mr-1.5" />}
                  {saving ? 'Saving...' : 'Save Changes'}
                </Button>
              </div>
            </Card>
          </TabsContent>

          {/* Contact Tab */}
          <TabsContent value="contact">
            <Card className="p-6 border-[#e2e8f0]/50 shadow-card space-y-6">
              <h3 className="font-heading font-semibold text-[#1e293b]">Contact Information</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="md:col-span-2">
                  <Label className="text-sm text-[#1e293b] font-medium flex items-center gap-2">
                    <Mail className="w-4 h-4 text-[#94a3b8]" /> Email
                  </Label>
                  <Input
                    value={authUser.email}
                    className="mt-1.5 border-[#e2e8f0] bg-[#f8fafc]"
                    disabled
                  />
                  <p className="text-xs text-[#94a3b8] mt-1">Email cannot be changed</p>
                </div>
                <div>
                  <Label className="text-sm text-[#1e293b] font-medium flex items-center gap-2">
                    <Phone className="w-4 h-4 text-[#94a3b8]" /> Phone
                  </Label>
                  <Input
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    className="mt-1.5 border-[#e2e8f0]"
                    disabled={loading}
                    placeholder="Your phone number"
                  />
                </div>
                <div>
                  <Label className="text-sm text-[#1e293b] font-medium flex items-center gap-2">
                    <Briefcase className="w-4 h-4 text-[#94a3b8]" /> Position
                  </Label>
                  <Input
                    value={position}
                    onChange={(e) => setPosition(e.target.value)}
                    className="mt-1.5 border-[#e2e8f0]"
                    disabled={loading}
                    placeholder="Job title or position"
                  />
                </div>
                <div className="md:col-span-2">
                  <Label className="text-sm text-[#1e293b] font-medium flex items-center gap-2">
                    <MapPin className="w-4 h-4 text-[#94a3b8]" /> Address
                  </Label>
                  <Input
                    value={address}
                    onChange={(e) => setAddress(e.target.value)}
                    className="mt-1.5 border-[#e2e8f0]"
                    disabled={loading}
                    placeholder="Your address"
                  />
                </div>
              </div>
              <div className="flex justify-end">
                <Button
                  className="bg-[#3b82f6] hover:bg-[#2563eb] text-white"
                  onClick={handleSaveProfile}
                  disabled={saving || loading}
                >
                  {saving ? <Loader2 className="w-4 h-4 mr-1.5 animate-spin" /> : <Save className="w-4 h-4 mr-1.5" />}
                  {saving ? 'Saving...' : 'Save Changes'}
                </Button>
              </div>
            </Card>
          </TabsContent>

          {/* Preferences Tab */}
          <TabsContent value="preferences">
            <Card className="p-6 border-[#e2e8f0]/50 shadow-card space-y-6">
              <h3 className="font-heading font-semibold text-[#1e293b]">Preferences</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <Label className="text-sm text-[#1e293b] font-medium flex items-center gap-2">
                    <Globe className="w-4 h-4 text-[#94a3b8]" /> Language
                  </Label>
                  <Input
                    value={language}
                    onChange={(e) => setLanguage(e.target.value)}
                    className="mt-1.5 border-[#e2e8f0]"
                    disabled={loading}
                    placeholder="e.g., English"
                  />
                </div>
                <div>
                  <Label className="text-sm text-[#1e293b] font-medium flex items-center gap-2">
                    <Globe className="w-4 h-4 text-[#94a3b8]" /> Preferred Language
                  </Label>
                  <Input
                    value={preferredLanguage}
                    onChange={(e) => setPreferredLanguage(e.target.value)}
                    className="mt-1.5 border-[#e2e8f0]"
                    disabled={loading}
                    placeholder="e.g., English"
                  />
                </div>
                <div className="md:col-span-2">
                  <Label className="text-sm text-[#1e293b] font-medium flex items-center gap-2">
                    <Clock className="w-4 h-4 text-[#94a3b8]" /> Timezone
                  </Label>
                  <Input
                    value={timezone}
                    onChange={(e) => setTimezone(e.target.value)}
                    className="mt-1.5 border-[#e2e8f0]"
                    disabled={loading}
                    placeholder="e.g., Australia/Sydney"
                  />
                </div>
              </div>
              <div className="flex justify-end">
                <Button
                  className="bg-[#3b82f6] hover:bg-[#2563eb] text-white"
                  onClick={handleSaveProfile}
                  disabled={saving || loading}
                >
                  {saving ? <Loader2 className="w-4 h-4 mr-1.5 animate-spin" /> : <Save className="w-4 h-4 mr-1.5" />}
                  {saving ? 'Saving...' : 'Save Changes'}
                </Button>
              </div>
            </Card>
          </TabsContent>

          {/* Security Tab */}
          <TabsContent value="security">
            <Card className="p-6 border-[#e2e8f0]/50 shadow-card space-y-6">
              <h3 className="font-heading font-semibold text-[#1e293b]">Change Password</h3>
              <div className="space-y-4 max-w-md">
                <div>
                  <Label className="text-sm text-[#1e293b] font-medium">Current Password</Label>
                  <div className="relative mt-1.5">
                    <Input
                      type={showCurrentPassword ? 'text' : 'password'}
                      value={currentPassword}
                      onChange={(e) => setCurrentPassword(e.target.value)}
                      className="border-[#e2e8f0] pr-10"
                      placeholder="Enter current password"
                    />
                    <button
                      type="button"
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-[#94a3b8] hover:text-[#64748b]"
                      onClick={() => setShowCurrentPassword(!showCurrentPassword)}
                    >
                      {showCurrentPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                    </button>
                  </div>
                </div>
                <div>
                  <Label className="text-sm text-[#1e293b] font-medium">New Password</Label>
                  <div className="relative mt-1.5">
                    <Input
                      type={showNewPassword ? 'text' : 'password'}
                      value={newPassword}
                      onChange={(e) => setNewPassword(e.target.value)}
                      className="border-[#e2e8f0] pr-10"
                      placeholder="Enter new password (min 6 chars)"
                    />
                    <button
                      type="button"
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-[#94a3b8] hover:text-[#64748b]"
                      onClick={() => setShowNewPassword(!showNewPassword)}
                    >
                      {showNewPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                    </button>
                  </div>
                </div>
                <div>
                  <Label className="text-sm text-[#1e293b] font-medium">Confirm New Password</Label>
                  <Input
                    type="password"
                    value={confirmPassword}
                    onChange={(e) => setConfirmPassword(e.target.value)}
                    className="mt-1.5 border-[#e2e8f0]"
                    placeholder="Confirm new password"
                  />
                  {newPassword && confirmPassword && newPassword !== confirmPassword && (
                    <p className="text-xs text-red-500 mt-1">Passwords do not match</p>
                  )}
                </div>
                <div className="pt-2">
                  <Button
                    className="bg-[#3b82f6] hover:bg-[#2563eb] text-white"
                    onClick={handleChangePassword}
                    disabled={changingPassword || !newPassword || !confirmPassword || newPassword !== confirmPassword}
                  >
                    {changingPassword ? <Loader2 className="w-4 h-4 mr-1.5 animate-spin" /> : <Lock className="w-4 h-4 mr-1.5" />}
                    {changingPassword ? 'Changing...' : 'Change Password'}
                  </Button>
                </div>
              </div>

              <div className="border-t border-[#e2e8f0] pt-6 mt-6">
                <h3 className="font-heading font-semibold text-red-600 mb-1">Danger Zone</h3>
                <p className="text-sm text-[#94a3b8] mb-4">Deactivating your account will disable your login and mark your profile as inactive.</p>
                <Button
                  variant="outline"
                  className="text-red-600 border-red-200 hover:bg-red-50"
                  onClick={async () => {
                    if (!confirm('Are you sure you want to deactivate your account? You will be logged out.')) return;
                    try {
                      await supabase.from('users').update({ is_active: 0 }).eq('id', userId);
                      await supabase.from('user_details').update({ status: 'INACTIVE' }).eq('user_id', userId);
                      toast.success('Account deactivated');
                      await supabase.auth.signOut();
                    } catch {
                      toast.error('Failed to deactivate account');
                    }
                  }}
                >
                  <UserX className="w-4 h-4 mr-1.5" /> Deactivate Account
                </Button>
              </div>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </DashboardLayout>
  );
}
