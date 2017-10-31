FROM drush/drush:8

ENV HOST_UID 1000
ENV HOST_GID 1000

#RUN adduser drush --gecos "" --home /home/drush  --disabled-password
RUN ln -s /usr/local/src/drush7/drush /usr/bin/drush
RUN apt-get update && apt-get install openssh-server mysql-client php5-mysql -y
RUN drush dl registry_rebuild-7.x
RUN drush cc drush

RUN mkdir /var/run/sshd
#RUN mkdir /home/drush/.ssh
#RUN mkdir /home/drush/.drush
#RUN chown drush:drush /home/drush/.ssh
#RUN chown drush:drush /home/drush/.drush -R

RUN sed -i 's/PermitRootLogin without-password/PermitRootLogin yes/' /etc/ssh/sshd_config

# SSH login fix. Otherwise user is kicked off after login
RUN sed 's@session\s*required\s*pam_loginuid.so@session optional pam_loginuid.so@g' -i /etc/pam.d/sshd

ENV NOTVISIBLE "in users profile"
RUN echo "export VISIBLE=now" >> /etc/profile

EXPOSE 22

ENTRYPOINT ["docker-entrypoint.sh"]
WORKDIR /source

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
