/**
 * ResetPassword - Password reset confirmation page
 * Handles the redirect from Supabase password reset email
 */
import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { supabase } from '@/lib/supabase';
import { Eye, EyeOff, AlertCircle, CheckCircle, Lock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from 'sonner';

export default function ResetPassword() {
  const navigate = useNavigate();
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const handleReset = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage('');

    if (!password.trim()) { setErrorMessage('Password is required'); return; }
    if (password.length < 6) { setErrorMessage('Password must be at least 6 characters'); return; }
    if (password !== confirmPassword) { setErrorMessage('Passwords do not match'); return; }

    setIsLoading(true);
    try {
      const { error } = await supabase.auth.updateUser({ password });
      if (error) {
        setErrorMessage(error.message);
      } else {
        setIsSuccess(true);
        toast.success('Password updated successfully!');
        setTimeout(() => navigate('/'), 3000);
      }
    } catch {
      setErrorMessage('An error occurred. Please try again.');
    }
    setIsLoading(false);
  };

  if (isSuccess) {
    return (
      <div className="min-h-screen bg-[#f8f9fb] flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-card border border-[#e2e8f0] p-8 max-w-md w-full text-center space-y-4">
          <div className="w-16 h-16 rounded-full bg-[#dcfce7] flex items-center justify-center mx-auto">
            <CheckCircle className="h-8 w-8 text-[#22c55e]" />
          </div>
          <h1 className="text-2xl font-bold text-[#1e293b] font-heading">Password Updated</h1>
          <p className="text-sm text-[#64748b]">
            Your password has been successfully updated. You'll be redirected to the login page shortly.
          </p>
          <Button
            onClick={() => navigate('/')}
            className="bg-[#3b82f6] hover:bg-[#2563eb] text-white"
          >
            Go to Login
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#f8f9fb] flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl shadow-card border border-[#e2e8f0] overflow-hidden max-w-md w-full">
        {/* Header */}
        <div className="bg-gradient-auth px-6 pt-6 pb-4">
          <h1 className="text-2xl font-bold text-white font-heading">Set New Password</h1>
          <p className="text-blue-100 mt-1 text-sm">Choose a strong password for your NytroLMS account</p>
        </div>

        {/* Form */}
        <div className="px-6 pb-6 pt-4">
          {errorMessage && (
            <div className="flex items-center gap-2 p-3 mb-4 rounded-lg bg-red-50 border border-red-200">
              <AlertCircle className="h-4 w-4 text-red-500 flex-shrink-0" />
              <p className="text-sm text-red-600">{errorMessage}</p>
            </div>
          )}

          <form onSubmit={handleReset} className="space-y-5">
            <div>
              <Label htmlFor="new-password" className="text-[#1e293b] font-semibold text-sm">New Password</Label>
              <div className="relative mt-1.5">
                <Input
                  id="new-password"
                  type={showPassword ? 'text' : 'password'}
                  placeholder="Min. 6 characters"
                  value={password}
                  onChange={(e) => { setPassword(e.target.value); setErrorMessage(''); }}
                  className="border-[#dbeafe] focus:border-[#3b82f6] pr-10 h-10"
                  autoComplete="new-password"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-[#94a3b8] hover:text-[#3b82f6] transition-colors"
                >
                  {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
            </div>

            <div>
              <Label htmlFor="confirm-password" className="text-[#1e293b] font-semibold text-sm">Confirm Password</Label>
              <Input
                id="confirm-password"
                type={showPassword ? 'text' : 'password'}
                placeholder="Re-enter your password"
                value={confirmPassword}
                onChange={(e) => { setConfirmPassword(e.target.value); setErrorMessage(''); }}
                className="mt-1.5 border-[#dbeafe] focus:border-[#3b82f6] h-10"
                autoComplete="new-password"
              />
            </div>

            <Button
              type="submit"
              className="w-full bg-[#3b82f6] hover:bg-[#2563eb] text-white font-semibold py-2.5 h-auto rounded-lg"
              disabled={isLoading}
            >
              {isLoading ? (
                <span className="flex items-center gap-2">
                  <span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                  Updating...
                </span>
              ) : 'Update Password'}
            </Button>
          </form>
        </div>
      </div>
    </div>
  );
}
