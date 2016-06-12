/* This is an SUID wrapper to run a command as some other User.
It allows the calling User to gain the permissions
as defined by the User below.
Written by DirectAdmin

WARNING: ensure you do excessive security checking.
If you use an SUID binary, you're giving anyone who
calls it access to that User.. so ensure the code
you use doesn't allow the caller to run arbitrary commands.

For example, if you only need access to this User to read data from a file,
then do not provide any variables, and grab the data directly from that file.
Don't run arbitrary commands through this binary via command line variables.

Also, if you're going to display senstive info from this binary to the
calling script, expect that it will be possible for the client to do
the same, without the script.  Extra checks to verify the calling script
would be required.

*** ONLY USE AN SUID WRAPPER AS A LAST RESORT ***

************************************/

//User we want to run as.
//To run as root, comment out the next line, else set the desired User.
#define RUN_AS_USER "admin"

//only allows accounts in the admin.list
//comment out the next line to allow anyone to call it.
//#define CALLED_BY_ADMIN_ACCOUNT_ONLY

/************************************/

#include <unistd.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <stdio.h>
#include <stdlib.h>
#include <pwd.h>
#include <string.h>
#include <errno.h>
extern int errno;

#define BUFF_LEN 128
#define ADMIN_LIST "/usr/local/directadmin/data/admin/admin.list"

int am_directadmin_child(void)
{
      FILE *fp = NULL;
      char path_buff[BUFF_LEN];
      char link_buff[BUFF_LEN];
      int ppid = getppid();
      while(ppid > 1)
      {
              snprintf(path_buff, BUFF_LEN, "/proc/%d/stat", (int)ppid);
              fp = fopen(path_buff, "r");
              if(fp == NULL)
              {
                      printf("Error reading %s: %s\n", path_buff, strerror(errno));
                      return 0;
              }

              fscanf(fp, "%*d %*s %*s %d", &ppid);
              fclose(fp);

              sprintf(path_buff, "/proc/%d/exe", (int)ppid);
              memset(link_buff, 0, BUFF_LEN);
              if (readlink(path_buff, link_buff, BUFF_LEN-1) == -1)
              {
                      printf("Error reading exe link: %s: %s\n", path_buff, strerror(errno));
                      return 0;
              }

              if (strncmp(link_buff, "/usr/local/directadmin/directadmin", 128) == 0)
                      return 1;
      }

      return 0;
}

#ifdef CALLED_BY_ADMIN_ACCOUNT_ONLY
int called_by_admin_account(const char *a)
{
      if (!a || !*a) return 0;

      FILE *fp = NULL;
      char account_buff[BUFF_LEN];
      fp = fopen(ADMIN_LIST, "r");

      if (fp == NULL)
      {
             printf("Error reading %s: %s\n", ADMIN_LIST, strerror(errno));
             return 0;
      }

      int i=0;               //buffer index
      int ch=0;       //read character
      int last=0;     //lookback character

      while (((ch = fgetc(fp)) != EOF) && (i<BUFF_LEN-1))
      {
             if (ch != '\n')
             {
                    account_buff[i++] = ch;
                    last = ch;
             }
             else
             {
                    account_buff[i] = '\0';
                    i=0;
                    if (!strncmp(account_buff, a, BUFF_LEN)) //match!
                    {
                           fclose(fp);
                           return 1;
                    }
             }
      }
      fclose(fp);
      return 0;
}
#endif

#define aTOz(ch) ('a'<=ch && ch<='z')
#define ATOZ(ch) ('A'<=ch && ch<='Z')
#define ZTON(ch) ('0'<=ch && ch<='9')
int safe_word_check(const char *word)
{
       if (!word || !*word) return 0;
       int len = strlen(word);
       if (len > 100)
       {
               printf("Option '%s' is too long\n", word);
               exit(10);
       }

       for (int i=0; i<len; i++)
       {
               if (aTOz(word[i])) continue;
               if (ATOZ(word[i])) continue;
               if (ZTON(word[i])) continue;
               switch(word[i])
               {
                       case '_' :
                       case '-' :
                       case '.' :
                               continue;
                               break;
               }

               printf("Option '%s' contains a disallowed character: '%c'\n", word, word[i]);
               exit(11);
       }
       return 1;
}

int main(int argc, char **argv)
{
      uid_t original_uid = getuid();
      struct passwd *pwd_caller = getpwuid(original_uid);
      if (pwd_caller == NULL) { printf("getpwuid error: %s\n", strerror(errno)); return 0; }
      if (!pwd_caller->pw_name || strlen(pwd_caller->pw_name) > 16)
      {
             printf("Couldn't get username from uid=%d\n", original_uid);
             exit(8);
      }
      char original_username[BUFF_LEN];
      strncpy(original_username, pwd_caller->pw_name, BUFF_LEN-1);

      if (*original_username == '\0')
      {
             printf("Caller username seems to be blank\n");
             exit(9);
      }

      if (setuid(0) == -1)
      {
              printf("Error setting to uid 0. Ensure %s is chmod to 4755\n", argv[0]);
              printf("setuid(0) error: %s\n", strerror(errno));
              exit(4);
      }

      if (setgid(0) == -1)
      {
              printf("Error setting to gid 0. Ensure %s is chmod to 4755\n", argv[0]);
              exit(5);
      }

      //We are now running as full root.

      if (!am_directadmin_child())
      {
              printf("Not a directadmin child\n");
              exit(6);
      }

#ifdef CALLED_BY_ADMIN_ACCOUNT_ONLY
      if (!called_by_admin_account(original_username))
      {
             printf("Not called by an Admin account (%s)\n", original_username);
             exit(7);
      }
#endif

#ifdef RUN_AS_USER
      struct passwd *pwd_info = getpwnam(RUN_AS_USER);

      if (pwd_info == NULL)
      {
              printf("Unable to get system information on %s: %s\n", RUN_AS_USER, strerror(errno));
              exit(1);
      }

      if (setgid(pwd_info->pw_gid) == -1)
      {
              printf("setgid(%d) error: %s\n", pwd_info->pw_gid, strerror(errno));
              exit(2);
      }

      if (setuid(pwd_info->pw_uid) == -1)
      {
              printf("setuid(%d) error: %s\n", pwd_info->pw_gid, strerror(errno));
              exit(3);
      }
#endif
      char *cmd_argv[] = { 0, 0, 0 };
      char cmd[] = "/usr/bin/cat";
      char path[] = "/home/admin/.kubecli.conf";

      struct stat filestat;
      if (stat(cmd, &filestat))
      {
              printf("Error with %s: %s\n", cmd, strerror(errno));
              exit(14);
      }

      cmd_argv[0] = cmd;
      cmd_argv[1] = path;

      clearenv();
      execv(cmd, cmd_argv);

      //will never get here because execv becomes the process.
      printf("Never going to see this\n");
      exit(0);
}